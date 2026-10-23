# Mise à jour ciblée des photos

Les cartes du catalogue et des nouveaux articles proposent une action aux seuls administrateurs : **Récupérer la photo manquante** lorsque le chargement échoue, ou **Mettre à jour la photo** lorsqu’elle est présente. Après le clic, **Demande enregistrée** confirme l’écriture sur ApiRecept. Le stock, les prix et les paniers sont inchangés.

Le site transmet l’UniqueId de l’article existant à `POST /api/v1/photo-requests`, avec la connexion ApiRecept enregistrée. La route du site vérifie `ROLE_ADMIN`, le CSRF et l’existence de l’article. La clé d’entreprise ne passe jamais dans le navigateur. La détection d’image manquante est faite au chargement effectif de l’image, y compris pour une source distante.

Dans ApiLocal, **Médias produits → MAJ photos manquantes** récupère et traite cette liste. Le même bouton couvre les demandes de récupération et de mise à jour. Les succès sont confirmés un par un ; les échecs restent à réessayer. Les révisions de demande évitent d’effacer une nouvelle demande arrivée pendant un transfert.

Après les transferts, actualiser le catalogue. Les URL intègrent la révision de publication ApiRecept, vérifiée au maximum une fois toutes les cinq secondes pour l’ensemble du site. En cas de panne API, le dernier état connu est conservé et la vérification est différée de trente secondes. La purge manuelle du cache des photos reste disponible.

## Installation

1. Déployer ApiRecept puis exécuter sa migration `Version20261021000100` avec `php bin/console doctrine:migrations:migrate --no-interaction --env=prod`. Elle crée `photo_refresh_request` et ajoute `company.photo_revision`.
2. Déployer les fichiers PHP, Twig, JavaScript et CSS SiteWeb, puis exécuter `php bin/console cache:clear --env=prod`.
3. Installer les fichiers ApiLocal puis le redémarrer. Vérifier l’URL et la clé de réception ainsi que les paramètres EBP et FTPS enregistrés.
4. Le site et ApiLocal doivent utiliser une clé de la même entreprise ApiRecept. Le dossier FTPS doit correspondre à la source publique des images du catalogue.

Aucune migration de la base SiteWeb ou ApiLocal n’est nécessaire. Les sources locales et les essais ne constituent pas un déploiement serveur.
