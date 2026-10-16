# Audit et harmonisation graphique - 16 octobre 2026

## Perimetre

Intervention graphique globale demandee par l'utilisateur, suivant
`CONTRAT_DESIGN.md`. Pages publiques, espace client, administration et composants
partages. Aucun changement des regles metier, des controleurs, des entites, des
formulaires soumis, des calculs ou des permissions dans cette intervention.

## Constats prioritaires

1. **Actions illisibles.** La couche connectee imposait du blanc aux boutons
   secondaires des en-tetes, tandis que le design system imposait leur fond blanc.
   Contraste mesure : **1:1**. Cas observes : profil, commandes, notifications et
   configuration de l'import.
2. **Orange sur blanc trop faible.** Plusieurs actions du catalogue et des remises
   utilisaient du texte orange sur fond blanc : contraste **2,26:1**. Les appels a
   l'action publics blancs sur orange presentaient un contraste de **2,36:1**.
3. **Palette divergente.** Ancien bleu `#315FBB`, ancien orange `#FF9100`, fonds
   bleutes/creme et plusieurs gris concurrents dans Bootstrap et les templates.
4. **Hierarchie surchargee.** Grands bandeaux degrades, ombres fortes, effets de
   verre et arrondis differents entre les pages de gestion.
5. **Superposition des modales.** Le bouton WhatsApp restait au-dessus du formulaire
   d'edition client. Le menu Parametres conservait une marge malgre son format plein
   ecran.

## Systeme visuel applique

| Role | Couleur |
| --- | --- |
| Surface | `#FFFFFF` |
| Fond secondaire | `#F5F6F8` |
| Texte principal | `#232B38` |
| Navigation, action standard, focus | `#315FB7` |
| Action mise en avant, accent | `#FF8A00` |

Les nuances de texte secondaire, bordures, ombres et survol sont derivees de cette
palette. Les tokens communs vivent dans
`templates/partials/_design_system.html.twig`. Les variables connectees et publiques
les reutilisent. Bootstrap est compile avec les memes couleurs dans
`assets/styles/app.scss`. Le document d'impression autonome reprend la palette.

- Actions standard : bleu, texte blanc ; survol bleu derive plus sombre.
- Actions orange : texte anthracite lisible, y compris sur l'accueil public.
- Actions secondaires : blanc avec texte et bordure bleus ; survol bleu/blanc.
- Suppression et deconnexion : rouge lisible ; confirmation positive : vert lisible.
  La signification des etats existants est conservee.
- En-tetes de gestion : blanc, titres anthracite, accent orange et sous-titres sobres.
- Formulaires : champs blancs, bordures lisibles, focus bleu ; champs en lecture
  seule grises sans changement de leur attribut ni de leurs droits.
- Tables : en-tetes gris clair, texte sombre, separateurs et survol discrets.
- Rayons et ombres harmonises ; les blocs internes du profil/dashboard sont alleges
  visuellement. Aucun champ ni indicateur n'est supprime.
- Photographies, logos, coordonnees, menus utiles et header public sticky conserves.

## Controle Page Par Page

| Ecran | Intervention et verification |
| --- | --- |
| Accueil public | Palette, appels a l'action, sections et footer ; photo du slide conservee ; introduction visible au chargement. |
| Connexion | Fond gris clair, formulaire centre conserve, carte plus sobre et bouton bleu lisible. |
| Accueil connecte | Tokens communs, titres, cartes et marques ; contenu et donnees conserves. |
| Catalogue et categorie | En-tete clair, Reset lisible, actions secondaires bleues, cartes et filtres harmonises. |
| Detail article | Couleurs, formulaires et actions communes ; affichage de stock conditionne par le role conserve. |
| Panier | Palette et tableau ; volet lateral teste en hauteur complete sur ordinateur et telephone. |
| Mes commandes | Bandeau et boutons corriges ; liste vide locale controlee. |
| Detail commande client/admin | Apercus Twig avec commande fictive en memoire, sans insertion en base. |
| Bon de commande | Bleu et gris de la charte ; rendu print verifie ; colonnes Reference, Article et Qte conservees. |
| Profil `/mon-profil` et `/profil` | Boutons lisibles, champs et statistiques harmonises ; permissions de lecture seule conservees. |
| Notifications | Filtres et actions lisibles, etats semantiques conserves. |
| Dashboard | Bandeau, indicateurs, tableaux et blocs internes harmonises ; donnees non modifiees. |
| Administration commandes | Liste et actions harmonisees ; aucune suppression ni transition envoyee. |
| Clients | Liste, formulaire modal et actions ; neuf champs, footer visible et corps defilable testes. |
| Configuration societe | Formulaire et actions harmonises par les composants partages. |
| Import et source d'import | Bandeaux, boutons, champs et panneaux harmonises ; aucun import lance. |
| Reductions categories | Toolbar, actions et tableaux harmonises. |
| Remises personnelles | Liste et formulaire de creation controles ; edition utilise le meme formulaire partage. |
| Gestion slides | Liste, creation et edition du slide existant controlees ; aucun media remplace. |
| Import utilisateurs WordPress | Formulaire et boutons harmonises ; aucune importation lancee. |
| Erreurs 403, 404 et 500 | Apercus des templates controles sur ordinateur et telephone. |
| Header, footer, menus, modales | Palette commune, actions et superpositions ; menu Parametres plein ecran et defilable. |

## Verification Et Limites

- 22 routes existantes, 31 combinaisons page/role et 62 rendus controles a 1440 et
  390 pixels, avec les comptes locaux administrateur et client.
- Aucun ancien bleu/orange detecte dans les couleurs de texte et de fond visibles
  des parcours controles. Aucun bouton textuel actif teste avec contraste inferieur
  a 4,5:1 sur fond uni. Aucun debordement horizontal detecte sur ces pages.
- Modale client controlee sur les deux formats, ainsi que les interactions des
  menus, les survols du profil et le volet panier dans les tests complementaires.
- Six apercus complementaires en memoire : commande admin, commande client, bon
  imprimable et erreurs 403/404/500. Aucune commande n'existe dans la base locale ;
  le lien historique `/admin/orders/2` renvoie 404 et n'a pas ete modifie.
- Les 49 templates passent `lint:twig`. Les trois tests PHP existants passent
  (six assertions). Build de production reussi, avec les avertissements de
  depreciation Sass/Bootstrap deja presents.
- Tests locaux sous Chrome headless : ils ne remplacent pas un controle sur iOS,
  Android physique, une imprimante ou les donnees de production. Les fonds en image
  ou en degrade ne sont pas couverts par le calcul automatique de contraste.
- Aucun formulaire metier enregistre, aucune commande creee, aucun import lance,
  aucune migration et aucun deploiement serveur effectues.

## Outils Reutilisables

`tools/audit-design.cjs` produit les captures et resultats dans
`tmp/design-audit/`. Il utilise Playwright et Chrome ; l'URL et les comptes peuvent
etre fournis par variables d'environnement `DESIGN_BASE_URL`,
`DESIGN_ADMIN_EMAIL`, `DESIGN_ADMIN_PASSWORD`, `DESIGN_CLIENT_EMAIL` et
`DESIGN_CLIENT_PASSWORD`. Les identifiants des fixtures locales servent uniquement
de valeurs par defaut pour le test.

`tools/render-design-previews.php` genere les apercus manquants avec des objets en
memoire et un cache de test isole. Il ne contient ni `persist` ni `flush`.

`tools/normalize-design-colors.cjs` controle les anciennes couleurs dans les blocs
CSS des templates. Son mode normal est une simulation ; `--write` applique la
correspondance de palette. Le balisage hors styles est verifie comme identique.
Les images, scripts metier, emails et templates d'impression autonomes sont exclus
de cette conversion mecanique.

## Finition Boutique Complementaire

A la demande suivante de l'utilisateur, une reference e-commerce automobile a ete
utilisee comme inspiration de finition, sans copie de son contenu ou de ses couleurs.

- Header public plus compact ; panneau de logo connecte simplifie.
- Titres de sections plus proportionnes, en-tetes alignes et recherche du selecteur
  de vehicule integree a son en-tete.
- Cartes catalogue et arrivages harmonisees : image non encadree a l'interieur de la
  carte, badges discrets, prix anthracite et commandes de quantite regulieres.
- Filtres repliables sur telephone, ouverts par defaut sur ordinateur ; leurs valeurs
  et leurs traitements restent identiques. Position sticky sous la hauteur reelle
  du header et defilement interne du panneau sur ordinateur.
- Marques partenaires, bande de chiffres et footer alleges visuellement.
- Verification supplementaire du catalogue a 1920, 1440, 768, 390 et 320 pixels,
  pour administrateur et client. Aucun controle de carte ne deborde ; les lignes
  de stock exact restent absentes pour le client.
- Tous les fichiers PHP dans `src/` et les scripts metier inclus dans les templates
  concernes sont identiques au debut de cette reprise. Le seul nouveau comportement
  JavaScript concerne la presentation du panneau de filtres.
- Aucun produit recent dans les donnees locales : le bloc d'arrivages garde son etat
  vide reel. Aucun produit ou faux arrivage n'a ete ajoute pour remplir la page.

Les nouvelles captures se trouvent dans `tmp/design-audit/storefront/` et
`tmp/design-audit/storefront-detail/`. Les limites de test locales indiquees plus haut
restent applicables, notamment pour les cartes externes Google Maps et les appareils
physiques.
