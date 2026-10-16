# Mise a jour manuelle de production - 16 octobre 2026

Racine locale du projet : `F:\3sk\3sk (1)`.
Tous les chemins ci-dessous sont relatifs a la racine Symfony. Les conserver
dans l'arborescence existante du serveur.

## Perimetre Et Fiabilite

Cette liste couvre le lot visuel actuel du 16 octobre : accueil, header public et
connecte, footer, connexion, catalogue/filtres/logos et harmonisation des pages.

Le dernier commit local remonte au 20 septembre ; il n'existe pas de commit de
debut de soiree. L'inventaire est donc recoupe avec les fichiers courants, leurs
dates, les references des templates et les modifications connues de cette session.
Il inclut les dependances de l'accueil realisees aujourd'hui, meme avant le soir.

**122 fichiers a transferer pour un lot complet.** Ce ne sont pas 122 fichiers
tous modifies ce soir : les trois logos ASZ/METELLI/MISFAT deja presents et les
bundles runtime/chunk sont inclus pour fournir un ensemble coherent. Des fichiers
peuvent deja etre identiques sur le serveur, auquel je n'ai pas fait de comparaison.

Le fichier texte voisin `output/deploiement-2026-10-16/fichiers-production.txt`
contient exactement ces 122 chemins, un par ligne, sans commentaires.

## Ordre Conseille

1. Sauvegarder les fichiers de production qui seront remplaces. Ne pas ecraser
   d'eventuelles modifications faites uniquement sur le serveur sans comparaison.
2. Transferer les nouveaux CSS, JavaScript, images et bundles de public/build.
   Les trois fichiers PHP listes plus bas doivent etre presents avant d'activer
   les templates qui utilisent leurs services ou fonctions Twig.
3. Transferer les sources frontend et les templates listes, de preference pendant
   une courte maintenance pour eviter un melange d'anciennes et nouvelles versions.
4. Envoyer entrypoints.json et manifest.json avec les bundles correspondants,
   les deux JSON en dernier. Ne pas supprimer immediatement les anciens fichiers
   hashes encore demandes par des pages deja ouvertes.
5. Purger le cache Symfony de production avec la commande ci-dessous, sous
   l'utilisateur PHP/deploiement habituel. Ne pas envoyer le cache local.

```bash
php bin/console cache:clear --env=prod --no-debug
```

Le nouveau service Twig ProductBrandExtension est indispensable : son absence ou
un ancien conteneur en cache peut provoquer une erreur sur product_brand_logos().
Si l'autoloader de production a ete configure en classmap authoritative, regenerer
aussi l'autoload selon la procedure du serveur ; aucune installation de nouvelle
dependance n'est demandee par ce lot.

Les bundles locaux sont deja des fichiers de production versionnes. Les quatre
references d'entrypoints.json/manifest.json existent et le JavaScript compile
contient bien la logique responsive des filtres. Pas besoin de lancer npm sur le
serveur si l'on transfere cet ensemble tel quel. Si un nouveau build est genere
localement, remplacer cette liste de noms hashes par ceux du nouveau manifest.

**Aucune migration ajoutee pour ce lot.** Ni composer.json, ni composer.lock,
ni la configuration ni les migrations n'ont de modification dans cet inventaire.

Si OPcache est configure pour ignorer les dates des fichiers, faire recharger PHP
selon les outils de l'hebergeur. Purger aussi le cache CDN eventuel, puis verifier
le site avec un rechargement complet du navigateur.

## Sources Frontend (2)

```text
assets/app.js
assets/styles/app.scss
```

## PHP (3)

```text
src/Controller/HomeController.php
src/Service/HomeVehicleBrands.php
src/Twig/ProductBrandExtension.php
```

## Templates Twig (26)

```text
templates/admin/clients/index.html.twig
templates/admin/hero_slide/index.html.twig
templates/admin/order/index.html.twig
templates/admin/order/print.html.twig
templates/articles/index.html.twig
templates/articles/show.html.twig
templates/base.html.twig
templates/home/_private_content.html.twig
templates/home/_public_content.html.twig
templates/home/sections/_about.html.twig
templates/home/sections/_contact.html.twig
templates/home/sections/_hero.html.twig
templates/home/sections/_new_articles.html.twig
templates/home/sections/_partner_brands.html.twig
templates/home/sections/_services.html.twig
templates/home/sections/_showcase.html.twig
templates/home/sections/_stats_strip.html.twig
templates/partials/_connected_theme.html.twig
templates/partials/_design_system.html.twig
templates/partials/_header.html.twig
templates/partials/_header_contacts.html.twig
templates/partials/_header_facts.html.twig
templates/partials/_phone_contact_modal.html.twig
templates/partials/_public_header.html.twig
templates/partials/_site_footer.html.twig
templates/security/login.html.twig
```

## CSS (11)

```text
public/styles/catalog-filters.css
public/styles/connected-ui.css
public/styles/header-common.css
public/styles/home-about-services.css
public/styles/home-contact.css
public/styles/home-layout.css
public/styles/login.css
public/styles/partner-brands.css
public/styles/public-showcase.css
public/styles/site-footer.css
public/styles/typography.css
```

## JavaScript Direct (2)

```text
public/scripts/product-brand-logos.js
public/scripts/public-showcase.js
```

## Images Et Logos (72)

```text
public/img/brand-logos/antai.jpg
public/img/brand-logos/ap.jpg
public/img/brand-logos/asz.png
public/img/brand-logos/de-ga.jpg
public/img/brand-logos/dega.jpg
public/img/brand-logos/depo.jpg
public/img/brand-logos/euro-bump.jpg
public/img/brand-logos/euro-stamp.jpg
public/img/brand-logos/eurobump.jpg
public/img/brand-logos/farolon.jpg
public/img/brand-logos/fowyad.jpg
public/img/brand-logos/isam.jpg
public/img/brand-logos/itsa.jpg
public/img/brand-logos/maxem.jpg
public/img/brand-logos/metelli.png
public/img/brand-logos/misfat.png
public/img/brand-logos/oran.jpg
public/img/brand-logos/phira.jpg
public/img/brand-logos/pleksan.jpg
public/img/brand-logos/poliplast.jpg
public/img/brand-logos/prasco.jpg
public/img/brand-logos/pulo.jpg
public/img/brand-logos/reshing-autoparts.jpg
public/img/brand-logos/root-automotive.jpg
public/img/brand-logos/root.jpg
public/img/brand-logos/sp.png
public/img/brand-logos/tursan.jpg
public/img/brand-logos/tyg.jpg
public/img/brand-logos/view-max.jpg
public/img/brand-logos/yih-sheng.jpg
public/img/design/footer-wave.svg
public/img/design/home-about-background.svg
public/img/design/home-services-background.svg
public/img/design/partner-brands-background.svg
public/img/payment/cmi-logo.png
public/img/payment/visa-logo.png
public/img/vehicle-brands/alfa_romeo.webp
public/img/vehicle-brands/audi.webp
public/img/vehicle-brands/bmw.webp
public/img/vehicle-brands/byd.webp
public/img/vehicle-brands/chevrolet.webp
public/img/vehicle-brands/citroen.webp
public/img/vehicle-brands/cupra.webp
public/img/vehicle-brands/dacia.webp
public/img/vehicle-brands/daihatsu.webp
public/img/vehicle-brands/fiat.webp
public/img/vehicle-brands/ford.webp
public/img/vehicle-brands/honda.webp
public/img/vehicle-brands/hyundai.webp
public/img/vehicle-brands/isuzu.webp
public/img/vehicle-brands/iveco.webp
public/img/vehicle-brands/jaguar.webp
public/img/vehicle-brands/jeep.webp
public/img/vehicle-brands/kia.webp
public/img/vehicle-brands/lancia.webp
public/img/vehicle-brands/land_rover.webp
public/img/vehicle-brands/mercedes.webp
public/img/vehicle-brands/mini.webp
public/img/vehicle-brands/mitsubishi.webp
public/img/vehicle-brands/nissan.webp
public/img/vehicle-brands/opel.webp
public/img/vehicle-brands/peugeot.webp
public/img/vehicle-brands/porsche.webp
public/img/vehicle-brands/range_rover.webp
public/img/vehicle-brands/renault.webp
public/img/vehicle-brands/seat.webp
public/img/vehicle-brands/skoda.webp
public/img/vehicle-brands/ssangyong.webp
public/img/vehicle-brands/suzuki.webp
public/img/vehicle-brands/toyota.webp
public/img/vehicle-brands/volkswagen.webp
public/img/vehicle-brands/volvo.webp
```

## Fichiers Compiles (6)

```text
public/build/runtime.81003d5f.js
public/build/663.cafdc20d.js
public/build/app.5b0974ba.js
public/build/app.dd586466.css
public/build/entrypoints.json
public/build/manifest.json
```

## Modification Plus Ancienne A Verifier Separement

`src/Controller/Api/CartApiController.php` est egalement modifie dans le depot,
mais son fichier local est date du **6 octobre 2026**, pas de ce soir. Son diff
masque les quantites exactes de stock dans les messages d'erreur aux clients
non administrateurs.

Il n'est pas inclus dans les 122 fichiers du lot visuel. Le deployer aussi seulement
si cette correction anterieure manque en production, apres comparaison. Si le but
est de deployer absolument toutes les differences de code actuellement en attente
dans le depot, l'ajouter au lot : cela fait alors 123 fichiers.

## A Ne Pas Transferer Pour Le Fonctionnement Du Site

- `tmp/` : captures, sauvegardes, journaux et apercus de test ; certaines traces
  contiennent des donnees de comptes locaux.
- `tests/`, `tools/`, `.phpunit.cache/`, `node_modules/`, `.git/`.
- `docs/`, `output/`, `AGENTS.md`, `CONTRAT_DESIGN.md`, cette checklist :
  documentation et livrables locaux, facultatifs dans un depot prive mais pas
  necessaires au serveur web.
- `public/img/brand-logos/README.md` : guide de nommage, facultatif, pas un logo.
- Ne pas remplacer `.env`, `.env.local`, les secrets ni `var/` par les versions
  locales. Conserver la configuration et les donnees propres a la production.
- Ne pas remplacer en bloc `vendor/`, `public/` ou `templates/` : transferer les
  seuls fichiers listes et conserver les autres fichiers du serveur.
- Ne pas ecraser les photos/articles/images de slides, fichiers CSV ni repertoires
  d'upload/synchronisation de production. Aucun nouveau slide n'est livre dans ce lot.

Les medias d'origine deja references dans le projet (logo complet, logo court,
photographie d'entrepot, slides et logos partenaires) doivent rester disponibles.
Ils n'ont pas ete remplaces par des visuels generes dans ce lot.

## Controle Apres Transfert

1. Accueil hors connexion : header, slide d'origine, bande constructeurs, sections
   de presentation/services, partenaires et footer.
2. A grande largeur, avantages proches d'Espace client. Apres connexion, aucun
   bloc des trois avantages, y compris sur le profil.
3. Connexion puis menus compte, notifications, panier et parametres administrateur.
4. Catalogue : filtres instantanes, annees, reset, pagination et defilement.
5. Logos des cartes charges ; nom conserve pour une marque dont l'image manque.
6. Mobile : menu accessible, absence de debordement et controles lisibles.
7. Profil, commandes et bon imprimable : liens presents, impression sans prix.
8. Aucun message d'erreur Twig ni erreur 500 dans les journaux du serveur.

Ce document prepare le transfert manuel. Aucun fichier n'a ete envoye au serveur.
