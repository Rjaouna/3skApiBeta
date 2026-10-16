# Audit Catalogue, Header et Typographie

Date : 16 octobre 2026. Intervention locale sur http://127.0.0.1:8765/.
Reference : CONTRAT_DESIGN.md, version 2.0.

## Corrections Appliquees

- Filtres compacts directement sous la navigation, sans bandeau Articles.
- Famille, marque, vehicule et disponibilite appliques des la selection.
  Annee appliquee au relachement du curseur, au clic ou au clavier ; texte apres
  300 ms de pause. Les espaces restent conserves dans le champ actif.
- Un seul reset dans le bloc : criteres, annees, recherche en attente et mode
  arrivages reinitialises. Aucun bouton Appliquer ou Effacer.
- Barre sticky sous le header a partir de 1200 px ; filtres repliables sur mobile.
- Requetes precedentes annulees ; leur terminaison ne deverrouille plus la
  requete courante. Le curseur n'est plus reinitialise inutilement a chaque reponse.
- Menu connecte plat, sans pastille de fond, avec indicateur fin et droit.
- Logos reels des cartes centralises dans public/img/brand-logos/, detectes
  automatiquement a partir du nom de fichier. Meme rendu dans catalogue et accueil.
- Commandes de quantite/panier contenues dans les cartes, y compris a 320 px.
  Texte anthracite sur les badges orange pour un meilleur contraste.
- Coordonnees et avantages du header factorises dans des partials communs.
  Meme identite publique/connectee : topbar bleue, compte orange, logo et recherche.
  Notifications, compte, parametres administrateur, voix, panier, arrivages et
  categories conservent leurs fonctions. Aucun lien vers une section inexistante.

## Echelle Typographique

Roboto pour la lecture ; Montserrat pour les titres et controles existants.
Tailles fixes par palier responsive, sans nouvelle police.

| Usage | Ordinateur | Telephone |
| --- | --- | --- |
| Texte courant / saisie | 16 px | 16 px |
| Bouton standard | 14 px | 14 px |
| Label / texte secondaire | 13 px | 13 px |
| Metadonnee / badge / entete de tableau | 12 px | 12 px |
| Titre de page applicative | 28 px | 24 px |
| Titre de panneau | 18 px | 18 px |
| Titre Presentation et Services publics | 48 px | 32 px |
| Texte de presentation | 18 px | 16 px |

Les autres titres publics conservent leur hierarchie (36/28 px notamment).
Les titres du hero valides restent inchanges. Les boutons importants peuvent
rester a 16 px ; les controles compacts a 12/13 px. Les documents imprimables
gardent leur echelle physique compacte, sans prix ; les emails ne sont pas recolores.

## Verifications Effectuees

- 54 templates Twig valides ; PHPUnit : 6 tests et 11 assertions passes.
- Catalogue client et administrateur sur 320, 390, 575, 768, 991, 1024, 1199,
  1440 et 1920 px : 18 controles de mise en page et 54 cycles ouverture/fermeture.
- Comparaison des identifiants et nombres d'articles affiches avec les reponses API.
  Filtres combines, curseur au clavier et a la souris, espaces saisis, reset,
  conservation des URL apres rechargement, pagination et chargement au defilement.
- Requetes chevauchantes avec reponses differees : seuls les derniers resultats
  restent affiches. Etat de chargement correct pendant la requete suivante.
- Toutes les URL des 30 fichiers de logos repondent en HTTP 200 ; noms avec
  accents/espaces/ponctuation normalises ; repli lisible si l'image echoue.
- Rendu des logos de l'accueil teste avec une reponse isolee contenant de vrais
  articles existants, sans ecriture en base ni ajout de fausses nouveautes au site.
- Accueil public et connexion sur les neuf largeurs : textes contenus, alignements
  conserves, header sticky. Comparaison publique/client/admin du header sur
  390, 1024, 1440 et 1920 px : coordonnees, typographie, logo, recherche et palette.
- Menu compte, panier lateral, menu mobile, modal Parametres plein ecran,
  modal client et survol du bouton de modification du profil verifies.
- Audit global : 78 controles/captures desktop et mobile, dont 12 apercus de
  commandes, bon imprimable et erreurs. Aucun debordement horizontal ni erreur
  JavaScript releve ; aucun probleme de contraste sur les boutons mesures.
- Aucun texte d'interface mesure sous 12 px ; seuls les bons imprimables gardent
  volontairement leur petite echelle. Les gradients ne sont pas mesures comme
  des fonds unis par l'audit automatique ; captures inspectees en complement.
- Requetes de mutation de panier bloquees pendant les tests specialises ; paniers
  inchanges avant/apres. Stock exact visible administrateur seulement, y compris
  sur la fiche article. Aucune commande, modification client ou import declenche.

Pages parcourues : accueil et connexion ; catalogue et fiche article ; panier,
commandes, consultation/edition du profil, notifications ; administration tableau
de bord, commandes, clients, configuration, import, source d'import, remises,
slides (liste/creation/edition) et import WordPress. Les formulaires de gestion
ont ete ouverts, pas soumis. Les etats commande/erreur absents ont des apercus
locaux generes sans persistance.

## Limites et Livraison

- La route locale /admin/orders/2 renvoie deja 404 avant intervention. Elle reste
  inchangee ; les apercus permettent de controler le design sans creer de commande.
- Plusieurs marques de la base n'ont pas de logo correspondant dans les medias
  retrouves. Leurs noms restent affiches. L'inventaire voisin liste les fichiers
  attendus ; ne pas confondre constructeurs automobiles et fabricants de pieces.
- Aucun controleur, entite, import, calcul de prix, remise, droit, CSRF ou regle de
  stock modifie dans cette intervention. Les medias valides restent inchanges.
- Pas de migration necessaire. Pas de rebuild Encore pour ces fichiers statiques.
  Au deploiement, livrer aussi la nouvelle extension Twig et les nouveaux assets,
  puis vider/rechauffer le cache Symfony de production selon la procedure du projet.
- Travail verifie localement seulement, non deploye sur le serveur public.

Traces : tmp/design-audit/delivery-verified/results.json et captures associees ;
tmp/instant-refinement/ ; scripts tools/check-instant-catalog.cjs,
check-instant-edge-cases.cjs, check-public-typography.cjs, check-header-parity.cjs.
Les captures et donnees de test peuvent contenir des informations de comptes
locaux ; ne pas publier ces traces brutes sur un serveur accessible au public.
