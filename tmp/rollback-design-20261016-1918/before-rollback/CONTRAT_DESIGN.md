# Contrat de design - 3SK Parts Auto

**Statut : actif. Version 1.6. Harmonisation sobre autorisée par le brief complet du 16 octobre 2026, sans refonte de l'identité ni des fonctionnalités.**

Ce contrat est la référence visuelle pour les prochaines interventions sur le projet.
Il concerne l'accueil public, la connexion, l'espace client, l'administration et leurs
composants partagés. Une nouvelle instruction explicite de l'utilisateur peut le faire
évoluer ; une préférence du développeur ne suffit pas.

## Maquette visuelle validée

![Version validée de l'accueil public](docs/design/accueil-reference-validee-2026-10-16.png)

Cette capture, fournie avec le message « voici la version validé », constitue la
référence de composition pour la partie supérieure de l'accueil public :

- Topbar bleue : coordonnées réelles, informations de service et unique action
  « Espace client » orange, menant à la connexion.
- Header blanc : logo à gauche, recherche et navigation utile, sans second bouton
  orange à côté de Contact. Le header reste sticky conformément à la demande précédente.
- Slider pleine largeur : visuel automobile, titre blanc/orange **sans italique**,
  texte lisible à gauche, trois avantages et deux actions fonctionnelles.
- Bande des constructeurs sur **fond gris clair**, avec vrais logos en
  anthracite/gris, noms lisibles et séparateurs fins. Pas de fond bleu pour cette bande.
- Action « Autres marques » uniquement si des marques supplémentaires existent,
  avec ouverture d'un contenu réel.

La palette HEX de la section 1 reste la source exacte des couleurs de l'interface ;
les nuances photographiques, ombres ou différences de rendu de la capture ne
définissent pas de nouvelles couleurs de marque.

Cette validation n'autorise pas à remplacer les médias du slider de sa propre
initiative ni à modifier les règles métier. Elle ne valide pas de changement sur
les sections ou pages absentes de la capture. La version mobile doit adapter cette
composition sans texte coupé ni navigation inutilisable.

L'archivage de cette référence ne signifie pas que toutes ses différences ont déjà
été intégrées ou déployées. Toute intervention reste limitée à la demande en cours.

## 1. Palette officielle

![Palette de référence fournie par l'utilisateur](docs/design/palette-reference.png)

Les valeurs suivantes sont relevées au centre des aplats de la capture fournie.
Elles font référence, même si certains anciens styles utilisent des nuances proches.

| Couleur | HEX exact | RGB | Usage |
| --- | --- | --- | --- |
| Blanc | `#FFFFFF` | `255, 255, 255` | Fond principal, header, formulaires et surfaces claires. |
| Gris clair | `#F5F6F8` | `245, 246, 248` | Fonds secondaires, zones de regroupement et en-têtes de tableaux. |
| Fond secondaire | `#F6F8FB` | `246, 248, 251` | Fond applicatif et alternance sobre des sections, selon le nouveau brief. |
| Texte secondaire | `#627187` | `98, 113, 135` | Variante très légèrement renforcée du `#64748B` proposé, pour garder au moins 4,5:1 sur les fonds clairs. |
| Bordure | `#E5E9F0` | `229, 233, 240` | Séparateurs et contours discrets ; variante plus contrastée pour les champs. |
| Anthracite | `#232B38` | `35, 43, 56` | Texte principal, titres, pictogrammes et détails contrastés. |
| Bleu marque | `#315FB7` | `49, 95, 183` | Navigation, liens, sélection et identité de marque. |
| Orange action | `#FF8A00` | `255, 138, 0` | Actions principales et accents visuels ciblés. |

### Règles de couleur

- Ne pas remplacer ces valeurs par des couleurs approchantes.
- Ne pas ajouter de nouvelle couleur décorative sans validation explicite.
- Privilégier les fonds blancs et gris clair ; ne pas introduire de grands blocs noirs
  ou bleu nuit sans demande explicite.
- Réserver l'orange aux actions et accents : ne pas en faire un fond dominant partout.
- Utiliser des variantes de survol, bordures et ombres dérivées de cette palette
  (opacité ou mélange avec le blanc/anthracite), plutôt que de nouveaux HEX arbitraires.
- Vérifier le contraste des textes, notamment sur les boutons orange ; employer
  l'anthracite lorsque le blanc ne reste pas suffisamment lisible.
- Conserver les couleurs fonctionnelles existantes des états métier : rouge pour
  indisponibilité/erreur, vert pour disponibilité/succès. Elles ne servent pas de
  couleurs décoratives et ne doivent pas modifier le sens des états existants.

### Tokens de référence

Lorsqu'une modification nécessite des couleurs, réutiliser les variables centrales
existantes et les aligner sur ces valeurs dans le périmètre autorisé :

```css
--brand-surface: #FFFFFF;
--brand-bg: #F6F8FB;
--brand-gray: #F5F6F8;
--brand-anthracite: #232B38;
--brand-ink: var(--brand-anthracite);
--brand-blue: #315FB7;
--brand-orange: #FF8A00;
--brand-muted: #627187;
--brand-border: #E5E9F0;
```

Si des variables Bootstrap, SCSS ou locales sont concernées, leurs valeurs HEX et RGB
doivent rester cohérentes avec ces tokens. Éviter les couleurs recopiées dans chaque
composant et les surcharges locales contradictoires.

## 2. Éléments à préserver

- **Images du slider :** conserver les médias configurés dans l'administration.
  Ne pas remplacer une image validée, ajouter un nouveau visuel devant elle, ni créer
  une variante générée sans demande explicite. Une demande de design ne vaut pas
  autorisation de changer les images.
- **Identité et informations :** conserver le logo, les coordonnées, les textes
  validés et les destinations existantes, sauf demande explicite portant dessus.
- **Header public :** rester sticky au défilement sur ordinateur et mobile. Les
  sections ciblées par le menu doivent rester visibles sous le header.
- **Navigation :** chaque lien ou bouton doit mener à une destination ou action réelle.
  Ne pas ajouter de menu vide, de lien factice ou d'action redondante sans utilité.
- **Marques :** utiliser les vrais logos et les marques réellement présentes dans
  le catalogue ; ne pas inventer de marques ni utiliser un libellé technique comme marque.
- **Composants partagés :** conserver un footer commun et des composants cohérents
  entre l'accueil, la connexion et l'espace connecté.

## 3. Typographie et mise en page

- Garder une hiérarchie claire et éviter le gras généralisé.
- Texte courant en poids normal ; labels, menus et boutons visuellement mesurés.
- Réserver les graisses fortes aux vrais titres et mises en avant utiles.
- Réutiliser les familles de caractères du projet, sans nouvelle police non validée.
- Conserver les alignements, espacements et largeurs validés dans la zone concernée.
- Éviter les fonds, cadres, ombres et blocs supplémentaires non demandés.
- Garantir la lisibilité sur téléphone, tablette et ordinateur : aucun texte coupé,
  chevauchement incohérent ou défilement horizontal involontaire.

## 4. Limite de l'intervention visuelle

Une modification graphique ne doit pas changer la logique de recherche, les règles
de stock, les prix, les remises, les commandes, les paniers, les imports, les droits
d'accès ou le fonctionnement des formulaires.

Les quantités de stock restent réservées à l'administrateur conformément aux règles
existantes ; un client normal voit le statut de disponibilité. Ne pas exposer de
données supplémentaires sous prétexte de changer leur présentation.

Ne pas supprimer une fonctionnalité existante pour simplifier une maquette.
Ne pas retoucher des zones sans rapport avec la demande et ne pas annuler des
modifications préexistantes de l'utilisateur.

## 5. Vérification avant livraison

1. Relire ce contrat et identifier le périmètre exact de la demande.
2. Vérifier la palette, les images et les informations conservées.
3. Tester les liens, boutons, menus, modales et formulaires concernés.
4. Vérifier le sticky, les ancres et le rendu responsive si ces composants sont touchés.
5. Contrôler les fichiers PHP/Twig/JavaScript concernés et inspecter des captures
   desktop/mobile pour une modification visible.
6. Signaler les vérifications impossibles et distinguer modification locale et déploiement.

## 6. Application à partir de maintenant

La création de ce contrat ne repeint pas automatiquement le site. Les différences
historiques sont corrigées lors d'une intervention autorisée dans la zone concernée,
ou lors d'une harmonisation globale explicitement demandée.

En particulier, les anciens bleu `#315FBB` et orange `#FF9100` ne doivent pas être
considérés comme la nouvelle référence : la palette officielle est celle de la section 1.

## 7. Direction boutique professionnelle

La référence complémentaire fournie par l'utilisateur le 16 octobre 2026 est
archivée dans `docs/design/boutique-inspiration-2026-10-16.png`. Elle sert de repère
de qualité, de hiérarchie et de composition e-commerce, **pas de modèle à copier**.
Elle ne remplace ni la palette officielle, ni les photos et informations du site.

- Header compact et lisible, recherche bien intégrée, navigation fonctionnelle.
- Titres proportionnés au contenu ; éviter les titres d'affiche dans les sections
  de catalogue et les blocs secondaires.
- Sections alignées, espacements réguliers, fonds sobres et accents orange ciblés.
- Cartes produits blanches avec images bien cadrées, informations hiérarchisées,
  prix lisibles et commandes de quantité régulières.
- Sur téléphone, filtres repliables sans suppression de champs ni modification des
  sélections ; les produits doivent rester faciles à atteindre.
- Footer partagé sobre, coordonnées conservées et absence de surcharge décorative.
- Ne pas inventer d'avis, notes, promotions, compte à rebours, produits, marques,
  rubriques ou liens pour ressembler à la référence.

L'évolution autorise un raffinement de la composition existante. Les obligations
de conservation des médias, du sticky et des règles métier restent inchangées.

## 8. Bloc marques partenaires

La référence `docs/design/marques-partenaires-reference-2026-10-16.png`, fournie
ensuite par l'utilisateur, définit le rendu demandé pour ce bloc partagé entre
l'accueil public et connecté :

- Fond très clair avec courbes bleu pâle atténuées. Le nouveau brief autorise
  la suppression du grand panneau flottant pour alléger la section.
- Mention « Nos marques phares » bleue précédée d'un court trait orange.
- Titre anthracite avec « partenaires » bleu, description alignée à droite sur desktop.
- Les 24 vrais logos existants en couleur, dans le même ordre, en six colonnes sur
  grand écran, avec des tuiles blanches à rayon de 8 px et bordure discrète.
- Écart de 24 px entre en-tête et grille ; pas de grandes marges artificielles.
- Grille adaptée en trois puis deux colonnes sur tablette et téléphone, sans débordement.

Cette précision ne change ni la bande des constructeurs située sous le slide, ni
les images du slide, ni les informations ou les règles métier.

## 9. Footer et typographie commune

La référence `docs/design/footer-reference-2026-10-16.png` définit le bas de page :
chiffres réels regroupés sur une surface blanche, fond automobile discret, identité
et coordonnées à gauche, paiement et aide, navigation, carte de localisation, puis
bande anthracite séparée par une courbe orange. Réutiliser les médias existants ;
les chiffres ne doivent jamais être copiés en dur depuis la capture.

Le footer reste partagé entre accueil, connexion, espace client et administration.
Les quatre numéros restent accessibles dans la modale existante. Facebook et
Instagram restent temporairement sur `#`, conformément à la réponse explicite de
l'utilisateur ; WhatsApp utilise le lien réel existant.

L'échelle commune est définie dans `public/styles/typography.css` :

| Usage | Ordinateur | Mobile |
| --- | --- | --- |
| Titre de page / connexion | 28 px | 24 px |
| Titre de section applicative | 24 px | 22 px |
| Titre de section publique | 40 px, puis 32 px sur tablette/laptop | 28 px |
| Titre de panneau | 18 px | 18 px |
| Titre de modale | 20 px | 20 px |
| Texte courant | 15 px | 15 px |
| Champs de saisie | 16 px | 16 px |
| Bouton standard / compact | 14 px / 13 px | 14 px / 13 px |
| Label / texte secondaire | 13 px | 13 px |
| En-tête de tableau / badge / métadonnée compacte | 12 px | 12 px |

Roboto devient la famille principale unique des textes, titres et contrôles.
Les icônes et les documents imprimables conservent leur rendu propre.
Les boutons importants peuvent conserver 16 px. Les titres
du slide et du bloc partenaires conservent leur hiérarchie spécifique validée :
ne pas les réduire à celle d'un titre de panneau. Utiliser des tailles fixes par
palier responsive, pas de taille de police proportionnelle à la largeur du viewport.

Les bons imprimables gardent leur échelle physique compacte adaptée au papier ;
les templates email ne sont pas soumis aux CSS du site. Ne pas augmenter leurs
espacements ni réintroduire de prix dans le bon de commande pendant un audit visuel.

## 10. Présentation et services de l'accueil public

Les références `docs/design/presentation-reference-2026-10-16.png` et
`docs/design/services-reference-2026-10-16.png` définissent ces deux blocs uniquement.

- Présentation : photo d'entrepôt à gauche, petit angle bleu et pictogramme colis.
  Fond clair sobre, sans motifs géométriques envahissants.
  À droite, titre « 3SK Parts Auto », textes existants et trois éléments
  2014, B2B et 1M avec leurs pictogrammes. Conserver ces informations.
- Services : titre proportionné sur deux lignes à gauche, quatre services en grille
  avec icônes orange et descriptions de la capture. À droite, carrousel à rayon de 8 px,
  flèches blanches, cinq indicateurs, texte de stock et lien réel vers la connexion.
- Réutiliser la photo d'entrepôt et les cinq visuels existants du carrousel.
  Le visuel POLIPLAST est présenté initialement ; les autres restent accessibles.
  Ne pas remplacer ces médias ou le slide principal par une image générée.
- Le nouveau brief harmonise ces titres à 40 px sur grand écran, 32 px en dessous
  de 1200 px et 28 px sur téléphone. Texte courant public de 16 px, services secondaires
  de 15 px. La hiérarchie applicative reste compacte et distincte de celle du hero.
- Employer le bleu et l'anthracite exacts de la palette, même si les captures
  présentent des nuances plus vives ou plus sombres.
- Sur téléphone, empiler les contenus, conserver tous les textes et cadrer les
  images du carrousel sans couper les pièces. Le carrousel et les effets de
  défilement existants restent fonctionnels.

Les styles de ces deux blocs sont isolés dans `public/styles/home-about-services.css`.
## 11. Harmonisation globale du 16 octobre 2026

Le dernier brief autorise le raffinement de l'ensemble du site et remplace les
dimensions et décors littéraux des anciennes captures lorsqu'ils contredisent
une présentation plus sobre. Il ne demande pas une reconstruction.

- Conserver environ 80 % de l'identité, tous les médias réels, les contenus importants,
  le sticky, les destinations et les interactions existantes.
- Centraliser les tokens dans `public/styles/design-tokens.css` et la hiérarchie
  dans `public/styles/typography.css`. Rayons de 8 px pour les cartes, 6 px pour
  les champs/boutons ordinaires ; exceptions existantes pour recherche et icônes rondes.
- Espacement public de 64/48/40 px selon les paliers ; marges latérales de 40/24/20/16 px.
- Contact : présentation et avantages professionnels à gauche, formulaire existant
  à droite, sans hauteur minimale artificielle. Conserver champs, CSRF, honeypot,
  contraintes de validation, requête et messages de résultat.
- Animations légères en opacité/translation, sans flou ; respecter la réduction
  des animations. Aucun média fictif, aucune dépendance ajoutée.
- Tester l'accueil à 1920, 1440, 1280, 1024, 768, 390, 360 et 320 px, ainsi que
  les composants communs sur les pages publiques, clients et administrateur.
- Ne pas modifier recherche, comptes, stock, commandes, imports ou base de données.
