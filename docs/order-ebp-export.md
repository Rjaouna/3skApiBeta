# Commandes du site vers EBP

Chaque commande validée démarre avec le statut commercial **Nouveau**. Dès qu’ApiRecept accepte son instantané, elle passe à **Exportée**. Le statut technique de transmission EBP est conservé séparément afin d’indiquer si ApiLocal a ensuite produit le CSV et l’a mis à disposition d’EBP.

## Flux automatique

1. Le site enregistre un instantané complet de la commande : coordonnées de facturation, code pays, prix HT/TTC avant remise, TVA, remise et lignes. Ces valeurs ne changent plus après la validation de l’achat.
2. ApiRecept garde un instantané idempotent de la commande avec l’état `exported`.
3. ApiLocal vérifie les commandes en attente chaque minute. Il crée dans `orders/` un CSV Windows-1252 par commande, strictement conforme au modèle d’import EBP : une ligne par article, séparateur `;`, date au format `JJ/MM/AAAA`, nombres avec virgule et prix/quantités sur huit décimales.
4. Après l’écriture atomique du CSV, ApiLocal confirme `available_in_ebp` à ApiRecept.
5. La commande Symfony `app:api-reception:sync-orders` actualise l’état final sur le site. La fiche client et la fiche administrateur indiquent alors « Mise à disposition EBP ».

Le nom du CSV comprend l’identifiant distant, le numéro de commande et la révision. Si ApiLocal redémarre ou si l’accusé de réception est perdu, le journal SQLite empêche la création d’un second fichier pour la même révision.

## Planification du site

Installer une tâche cron sur le serveur du site, toutes les minutes :

```cron
* * * * * cd /chemin/vers/SiteWeb && php bin/console app:api-reception:sync-orders --env=prod --no-debug --no-interaction >> var/log/order-ebp-sync.log 2>&1
```

Le connecteur doit rester démarré sur le poste qui a accès à EBP. L’interface **Commandes site** permet aussi de lancer immédiatement la récupération, visualiser le traitement actif et consulter les derniers CSV créés.

Pour les essais locaux sans cron, le bouton **Exporter les commandes nouvelles** de la liste administrateur envoie toutes les commandes au statut **Nouveau** à ApiRecept immédiatement. La fiche d’une commande conserve aussi le bouton **Exporter vers EBP** pour une action ciblée. Après l’export CSV par ApiLocal, lancez manuellement la mise à jour des états :

```bash
php bin/console app:api-reception:sync-orders --no-interaction
```

## Déploiement

Déployer ApiRecept et SiteWeb avant d’activer ce flux, puis lancer les migrations dans chaque projet :

```bash
# ApiRecept
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console cache:clear --env=prod --no-debug

# SiteWeb
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console cache:clear --env=prod --no-debug
```

Enfin, mettre ApiLocal à jour et redémarrer son processus. La même URL ApiRecept et la même clé d’entreprise déjà utilisées pour les exports catalogue sont utilisées pour les commandes.

Les commandes déjà créées avant cette évolution restent exportables lorsque leur article existe encore sur le site : ApiLocal reconstruit alors le HT et la TVA depuis le prix catalogue. Si l’article a été supprimé ou ne porte plus de prix HT/TVA exploitable, l’export est arrêté avec un message explicite afin d’éviter de créer une commande EBP erronée.
