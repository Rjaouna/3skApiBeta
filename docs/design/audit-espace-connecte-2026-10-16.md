# Audit et harmonisation de l'espace connecté

Date : 16 octobre 2026. Intervention locale, sans déploiement serveur.

## Périmètre et constats

L'accueil public et la connexion servent de références visuelles. Le catalogue
réservait environ un quart de sa largeur aux filtres. Les pages connectées
cumulaient plusieurs couches historiques : titres et actions irréguliers,
formulaires très anguleux, panneaux et bordures sans hiérarchie commune.
Sur téléphone, le tableau clients écrasait les noms et le bouton WhatsApp pouvait
recouvrir les champs en raison de sa position initiale au milieu de l'écran.

Les informations métier présentes avant intervention sont conservées. Aucun
chiffre, client, article, offre, marque ou contenu commercial n'a été inventé.

## Corrections appliquées

- Filtres au-dessus du catalogue et grille d'articles sur toute la largeur utile.
- Quatre critères alignés sur la première ligne, année/stock/actions dessous.
- Bandeau compact, sans ascenseur intérieur ; ouverture/fermeture conservée.
- Bloc replié initialement sur mobile, sans perte des valeurs sélectionnées.
- Topbar bleue, recherche claire, sélection des menus soulignée d'orange.
- Titres anthracite à accent orange, panneaux blancs, bordures bleues discrètes.
- Actions principales orange à texte anthracite ; actions secondaires bleues.
- Champs, tableaux, panneaux et focus clavier harmonisés ; états rouges/verts conservés.
- Tableau clients lisible avec défilement horizontal contenu sur petit écran.
- WhatsApp positionné en bas par défaut, toujours déplaçable et mémorisé.
- WhatsApp masqué pendant l'ouverture des filtres mobiles pour dégager les actions,
  puis réaffiché à la fermeture du bloc.

## Revue des pages

| Page ou groupe | Vérification et traitement |
| --- | --- |
| Accueil public et connexion | Contrôle de non-régression ; pas de nouvelle feuille connectée chargée. |
| Accueil connecté | Header, catégories, titres et actions harmonisés ; visuels et vrais logos conservés. |
| Catalogue et catégories | Filtres horizontaux ; critères, URL, résultats et grille vérifiés. |
| Fiche article | Panneau et actions harmonisés ; références, prix et droits de stock inchangés. |
| Panier | Présentation harmonisée ; quantités et montants conservés, aucune commande envoyée. |
| Mes commandes | Liste, filtres, états et détails ; détail également contrôlé en aperçu temporaire. |
| Mon profil et modification du profil | Champs et boutons lisibles, même organisation et mêmes données. |
| Notifications | Palette, actions et états vides conservés. |
| Tableau de bord administrateur | Titres, panneaux et tableaux ; chiffres et graphiques réels conservés. |
| Clients | Liste, recherche, filtres, pagination et modale d'édition contrôlés. |
| Configuration société | Présentation du formulaire uniquement ; aucune valeur enregistrée. |
| Import articles et source CSV | Panneaux, progression et commandes visuelles ; aucun import déclenché. |
| Réductions par catégorie | Champs, actions et tableaux harmonisés sans modifier les remises. |
| Remises client/famille | Liste et formulaire de création contrôlés ; création/édition partagent le même formulaire. |
| Gestion des slides | Liste, création et édition contrôlées ; aucun média remplacé ou enregistré. |
| Import utilisateurs WordPress | Présentation harmonisée ; aucun fichier importé. |
| Menus, modales et panneaux latéraux | Compte, paramètres plein écran, burger, panier et édition client contrôlés sur mobile/desktop. |
| Erreurs 403/404/500 | Contrôles en aperçus temporaires, sans changement du contenu. |
| Bon imprimable | Contrôle écran/impression ; colonnes Référence, Article et Qté, sans réintroduire les prix. |
| Emails et exports | Hors refonte visuelle ; sources inchangées. |

## Vérifications

- Audit global : 78 entrées de vérification de pages/états sur ordinateur et mobile,
  dont 12 aperçus temporaires. Menus et panneaux associés contrôlés en complément.
- Catalogue : comptes administrateur et client, 9 largeurs de 320 à 1920 px,
  3 cycles d'ouverture/fermeture par largeur et conservation des sélections.
- Recherche avec espace final, famille, marque, véhicule, intervalle d'années,
  disponibilité, application, effacement et rechargement de l'URL contrôlés.
- Les articles affichés correspondent aux réponses de l'API ; pagination,
  changement de limite et chargement automatique au défilement vérifiés.
- Quantités exactes réservées à l'administrateur, sur les cartes et le détail.
- Réponse du panier identique avant/après les premières séries pour chaque rôle.
  Une variation concurrente a été observée ensuite ; aucune restauration n'a été
  effectuée. Les contrôles finaux surveillent les requêtes et interdisent les
  mutations de panier pour garantir qu'ils n'envoient aucune modification.
- Scripts du catalogue et identifiants des contrôles strictement identiques.
- Empreintes des sources PHP, configuration, migrations, scripts applicatifs,
  SCSS et bon imprimable conservées pendant l'intervention.
- 52 templates Twig valides ; PHPUnit : 3 tests, 6 assertions, tous réussis.
- Contrastes mesurés avec conversion des couleurs calculées par le navigateur,
  y compris les formats modernes utilisés pendant les transitions.

## Limites et sécurité

La route locale `/admin/orders/2` répondait déjà 404 avant les modifications.
Les détails de commande ont donc aussi été rendus avec des objets temporaires
en mémoire, sans écriture en base. Aucun article, compte, remise ou commande
n'a été créé pour les besoins des vérifications.

Les actions d'enregistrement, d'import, de suppression et de changement de
statut n'ont pas été déclenchées. L'audit valide leur présentation, leurs champs
et leurs destinations conservées, pas une transaction réelle sur les données.
L'édition d'une remise sans enregistrement local disponible repose sur le
formulaire partagé avec la création, pas sur la modification d'un compte réel.

## Fichiers et traces

- `public/styles/connected-ui.css` : habillage connecté uniquement.
- `public/styles/catalog-filters.css` : composition du bandeau de filtres.
- `templates/articles/index.html.twig` : disposition, classes et associations labels/champs.
- `templates/base.html.twig` : chargement conditionnel du style et position initiale du bouton flottant.
- `tools/audit-design.cjs` : mesure des couleurs calculées fiabilisée.
- `tmp/connected-refresh/before-source/` : sauvegardes ciblées avant intervention.
- `tmp/design-audit/connected-baseline/` : état visuel avant intervention.
- `tmp/design-audit/connected-verified/` : captures et résultats de l'audit final.
- `tmp/connected-refresh/` : contrôles détaillés du catalogue et captures complémentaires.

Les sauvegardes ne doivent pas servir de restauration globale : le projet
contient d'autres modifications préexistantes qui ne sont pas annulées.
