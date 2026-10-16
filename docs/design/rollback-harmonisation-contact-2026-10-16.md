# Restauration du design premium et harmonisation du contact

Date : 16 octobre 2026. Intervention locale, non déployée sur le serveur public.

## 1. Résultat du rollback

La dernière harmonisation générale a été annulée avant de travailler sur le contact.
Le header bleu/blanc, le hero automobile, la bande véhicules, la présentation
d'activité, le bloc DEPO avec ses formes, les cartes 2014/B2B/1M, les Services,
le carrousel, les marques partenaires, les statistiques et le footer ont retrouvé
leur présentation précédente.

La palette initiale, les deux familles Roboto/Montserrat, les dimensions spécifiques
des titres, les arrière-plans, les arrondis et les ombres validés ont été restaurés.
La photographie principale et les autres médias n'ont pas été remplacés.

Le statut Git et les six derniers commits ont été examinés. Les changements récents
n'étant pas commités, une restauration globale depuis HEAD aurait écrasé du travail
antérieur. La restauration a donc été réalisée fichier par fichier à partir de
l'historique de l'intervention, avec vérification des extraits disponibles et des
transformations inversées. Aucun reset, checkout global, commit ou changement de
branche n'a été effectué.

Sauvegarde préalable : `tmp/rollback-design-20261016-1918/before-rollback/`.
Versions restaurées conservées : `tmp/rollback-design-20261016-1918/restored-baseline/`.

### Fichiers restaurés en phase 1

- `CONTRAT_DESIGN.md`
- `assets/app.js`
- `assets/styles/app.scss`
- `templates/base.html.twig`
- `templates/partials/_design_system.html.twig`
- `templates/partials/_public_header.html.twig`
- `templates/home/_public_content.html.twig`
- `templates/home/sections/_about.html.twig`
- `templates/home/sections/_services.html.twig`
- `templates/home/sections/_partner_brands.html.twig`
- `public/styles/typography.css`
- `public/styles/public-showcase.css`
- `public/styles/home-about-services.css`
- `public/styles/partner-brands.css`
- `public/styles/site-footer.css`

Les ajouts de la dernière harmonisation générale ont été retirés :
`public/styles/design-tokens.css`, `public/styles/public-home.css` et son script
de test dédié. Le bloc contact de cette intervention a d'abord été retiré lui aussi.
Les assets Encore ont été reconstruits avec les sources restaurées.

## 2. Incohérences conservées hors périmètre et problème corrigé

Les grandes formes, les rayons différents et la hiérarchie propre aux sections
validées ne sont plus traités comme des défauts à simplifier. Ils sont protégés.

Le contact, en revanche, conservait une hauteur minimale et de grandes marges qui
créaient du vide autour d'un accès client peu développé. Son formulaire avait une
présentation plus générique que les blocs voisins, des labels en capitales et des
espacements sans lien clair avec les Services ou les cartes déjà validées.

## 3. Harmonisation limitée au contact

- Accès professionnel à gauche, formulaire existant à droite.
- Avantages réels du catalogue, du panier et du suivi des commandes mis en évidence.
- Coordonnées existantes et ouverture de la modale des quatre numéros.
- Fond clair avec motif subtil déjà présent dans le projet.
- Formulaire blanc arrondi, ombre douce, contours et accents de la palette initiale.
- Titres, labels, champs, boutons et focus lisibles, sans espace vide artificiel.
- Empilement sur tablette/mobile et champs sur une colonne sur petit écran.
- Aucun changement sur les sections protégées pour les adapter au contact.

### Fichiers concernés en phase 2

- `templates/home/_public_content.html.twig` : inclusion du bloc contact et de son CSS uniquement.
- `templates/home/sections/_contact.html.twig` : nouveau composant contact isolé.
- `public/styles/home-contact.css` : règles exclusivement ciblées sur `#contact.contact-premium`.
- `CONTRAT_DESIGN.md` : version 1.7, protection explicite des sections restaurées.

Le formulaire a conservé exactement son action, ses noms de champs, ses contraintes,
son token CSRF, son honeypot et son script d'envoi. Les scripts de cookies et les autres
parties de l'accueil sont identiques à l'état restauré. Aucun fichier backend, aucune
règle métier et aucune donnée de base n'ont été modifiés par cette intervention.

## 4. Vérifications réellement exécutées

| Contrôle | Résultat |
| --- | --- |
| Sources protégées et médias | 16 contrôles réussis : 13 fichiers restaurés restés identiques pendant la phase 2, deux contrôleurs et la photo principale inchangés. |
| Parties de l'accueil hors contact | Comparaison exacte des sources réussie ; scripts et formulaire conservés à l'identique. |
| Blocs protégés | 54 comparaisons : neuf blocs à 1920, 1440, 1024, 768, 390 et 320 px ; mêmes dimensions, fonds, rayons et typographies. |
| Contact | Huit largeurs de 320 à 1920 px ; aucun débordement, alignement des champs et tailles contrôlés. |
| Formulaire | Validation native, réponse d'erreur et réponse de succès simulées, valeurs conservées en erreur, reset en succès, bouton réactivé. Aucun email réel envoyé. |
| Téléphones | Les quatre liens téléphoniques restent accessibles dans la modale à toutes les largeurs testées. |
| Header/hero/navigation | Huit largeurs ; sticky, menu mobile, ancres, recherche protégée par connexion et photo d'origine vérifiés. |
| Présentation/Services | Neuf largeurs ; décors, titres, données, images, cinq indicateurs, flèches et autoplay vérifiés après rollback. |
| Marques partenaires | 17 cas publics/client/admin ; 24 vrais logos, grille 6/3/2 colonnes et absence de débordement. |
| Footer/statistiques | 14 cas publics/client ; cinq indicateurs réels et quatre numéros conservés. |
| Audit commun | 78 mesures de pages et états publics/client/admin à 1440 et 390 px : aucun débordement, aucune erreur JavaScript ni anomalie de contraste de bouton détectée par l'outil. |
| Catalogue | Dix cas admin/client : contrôles non coupés, filtres adaptés ; aucune ligne de quantité exacte du stock pour le client normal. |
| Twig | 52 templates valides. |
| PHPUnit | Trois tests, six assertions réussis. |
| Build | Succès ; 21 avertissements de dépréciation Sass/Bootstrap déjà présents. |
| Git | `git diff --check` réussi ; seuls avertissements de conversion LF/CRLF. |

Les captures sont conservées dans `tmp/rollback-design-20261016-1918/`.
Pour les comparer, les animations, éléments flottants et la carte externe ont été
stabilisés ou masqués uniquement dans le navigateur de test. Les sept blocs supérieurs
ont un écart raster maximal inférieur à 0,004 %. Les statistiques et le footer changent
de position dans le document à cause de la nouvelle hauteur du contact : leur écart
raster atteint 1,76 %, mais leurs sources CSS, dimensions et styles mesurés sont
identiques. Ces deux blocs ont également été comparés visuellement.

## 5. Limites et déploiement

- La commande locale `/admin/orders/2` renvoie déjà 404 avant cette intervention.
  Cette absence de donnée n'a pas été corrigée pendant le travail graphique ; les
  vues de commande et d'impression ont été vérifiées avec des aperçus en mémoire.
- La livraison réelle des emails n'a pas été testée ; les réponses du formulaire
  ont été interceptées dans le navigateur pour éviter un envoi réel.
- L'URL et les coordonnées Google Maps sont conservées. L'affichage distant de la
  carte dépend du service externe et n'est pas certifié par la comparaison masquée.
- Les liens Facebook/Instagram temporaires restent sur `#`, comme demandé auparavant.
- Aucun déploiement distant ni migration. Les fichiers de `tmp/` sont des sauvegardes
  et des preuves de test, pas des fichiers à publier.

Accueil local : http://127.0.0.1:8765/
