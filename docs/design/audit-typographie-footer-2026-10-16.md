# Audit typographique et refonte du footer

Date : 16 octobre 2026. Intervention locale, sans déploiement sur le serveur public.
Référence : `footer-reference-2026-10-16.png`. Palette : contrat de design v1.4.

## Constats corrigés

- Des métadonnées et badges descendaient à 10 ou 11 px, notamment sur les cartes
  produits, les tableaux, le dashboard et les petits écrans.
- Les titres et labels utilisaient des tailles locales différentes pour un même
  usage : connexion, administration, profils, imports et catalogue.
- Les styles du footer étaient répartis entre plusieurs gabarits et une surcharge
  globale, ce qui empêchait d'obtenir un rendu partagé régulier.
- Le montant du panier mobile était tronqué. Il dispose maintenant d'une zone
  adaptée à sa taille de texte, y compris pour des montants longs.

## Échelle appliquée

| Élément | Ordinateur | Mobile |
| --- | --- | --- |
| Titre de page | 28 px | 24 px |
| Section applicative | 24 px | 22 px |
| Section publique | 36 px | 28 px |
| Panneau / modale | 18 px / 20 px | 18 px / 20 px |
| Texte courant / champ | 15 px / 16 px | 15 px / 16 px |
| Bouton standard / compact | 14 px / 13 px | 14 px / 13 px |
| Label / texte secondaire | 13 px | 13 px |
| En-tête de tableau / badge / métadonnée | 12 px | 12 px |

Les titres du slide et du bloc partenaires gardent leur hiérarchie validée.
Les appels à l'action importants peuvent conserver 16 px. Les polices existantes
Roboto et Montserrat sont conservées. Les tailles utilisent des paliers fixes,
sans police proportionnelle à la largeur de l'écran.

## Bas de page

Le footer partagé reprend la composition de la capture : identité et coordonnées,
blocs paiement et aide, navigation, carte, fond automobile discret et séparation
orange courbe au-dessus de la bande anthracite. Les photos existantes sont
réutilisées, sans modifier le média du slide.

Les cinq chiffres restent issus des données existantes, avec l'année 2014 inchangée.
Les quatre numéros de téléphone restent dans la modale. L'adresse, l'email et les
coordonnées Google Maps sont conservés. Facebook et Instagram restent sur `#`
sur instruction explicite de l'utilisateur ; WhatsApp utilise le lien existant.

## Pages contrôlées

| Zone | Écrans et états |
| --- | --- |
| Public | Accueil, connexion, footer, recherche, ancres, téléphones, menu mobile, sticky. |
| Client | Accueil connecté, catalogue, détail article, panier, commandes, profil, modification du profil, notifications. |
| Administration | Dashboard, clients et modale d'édition, commandes, configuration société, import articles, source d'import, réductions par catégorie, remises personnelles et formulaire de création, slides et formulaires de création/modification, import WordPress. |
| Composants | Menu Paramètres plein écran, menu du compte, menu mobile, panneau panier. |
| Aperçus sans écriture en base | Détail de commande admin/client, bon imprimable, erreurs 403/404/500. |

## Vérifications

- Audit général : 78 relevés sur 30 chemins ou états distincts, à 1440 et 390 px.
  Aucun débordement horizontal, aucune erreur JavaScript et aucun contraste de
  bouton insuffisant détectés par le contrôle automatisé dans ces échantillons.
- Footer : 14 cas public/client à 1920, 1680, 1440, 1024, 768, 390 et 320 px ;
  contenu non tronqué, cinq statistiques, adresse et quatre liens téléphoniques vérifiés.
- Typographie : 21 contrôles complémentaires sur catalogue, panier, clients,
  dashboard, imports et profil, à 1440, 390 et 320 px. Tailles des titres, labels,
  en-têtes et badges vérifiées ; simulation de montants longs uniquement dans le DOM.
- Catalogue : dix cas admin/client entre 1920 et 320 px. Filtres utilisables,
  commandes non débordantes et quantités exactes de stock toujours absentes pour
  le compte client normal.
- Header public : huit tailles entre 1920 et 320 px ; sticky, ancres, recherche,
  téléphone et image originale du slide vérifiés.
- Syntaxe : les 49 fichiers Twig sont valides.
- Tests existants : PHPUnit, 3 tests et 6 assertions réussis.

Les captures et relevés sont dans `tmp/design-audit/typography-final/`,
`tmp/footer-qa/`, `tmp/typography-checks/`, `tmp/showcase-qa/` et
`tmp/design-audit/storefront-detail/`.

## Limites et éléments préservés

- `/admin/orders/2` renvoie déjà une 404 dans les données locales, avant cette
  intervention. Le gabarit de détail a donc été vérifié avec une entité en mémoire,
  sans créer de commande. Ce constat de données/routage n'a pas été corrigé ici.
- Les formulaires ont été inspectés, mais aucun import, enregistrement, suppression
  ou envoi de commande n'a été lancé pour l'audit graphique.
- Le bon imprimable conserve ses tailles physiques compactes et ses colonnes
  Référence, Article, Qté. Aucun prix n'y a été réintroduit.
- Les emails restent indépendants des CSS du site et n'ont pas été redessinés.
- Google Maps est un service externe ; son chargement dépend du réseau et du navigateur.
- Les liens informatifs historiques du footer vers les ancres publiques sont
  conservés ; leur disponibilité sur l'accueil connecté n'a pas été modifiée par
  cet audit typographique.
- Aucun contrôleur, service métier, règle de stock, prix, remise, import, panier,
  droit d'accès ou schéma de base n'a été modifié dans cette intervention.

## Maintenance

Les tailles communes sont dans `public/styles/typography.css` et la composition
du bas de page dans `public/styles/site-footer.css`. Le footer et les statistiques
gardent chacun leur gabarit partagé. Réutiliser ces règles au lieu d'ajouter des
tailles arbitraires dans une nouvelle page.
