# Public Homepage Artwork

The reference composition is implemented as live HTML/CSS, not a flattened screenshot.
Logo assets and contact details are from the existing project. Vehicle brands are selected
from actual catalog data by `HomeVehicleBrands`; unknown labels are not invented as brands.
The public catalog, vehicle filters and search remain behind the existing login requirement.

## Slide Media

The homepage uses the first enabled slide from the existing administration as its
initial background, followed by the remaining enabled slides. No generated background
is used. The original slide files and database records have not been changed.

The discarded generated backgrounds have been removed from the project's public assets.
`prepare_showcase_assets.php` only converts existing vehicle PNG logos to 150 px wide
WebP copies without overwriting the originals.

## Presentation

The public header stays sticky while scrolling, including on the login page. The exact
site blue `#315fbb` and orange `#ff9100` are shared CSS tokens for the new public components.
The first slide keeps the new presentation; later slides retain their configured media,
text and links. Controls are only displayed when there is more than one slide.
The private homepage is unchanged.
