# H9E — Publication Master Lock

Version: **0.9.5**

## Default state after upgrade
`LOCKED — NOINDEX PILOT`

Final routes remain available for owner QA:
- `/oteller/`
- `/mekke-otelleri/`
- `/medine-otelleri/`
- `/otel/{frozen-public-slug}/`

While locked:
- public Hotel/Hub pages = `noindex, follow`
- self-canonical = suppressed
- STHI sitemap pages = 0 / absent from root sitemap index
- preview pages remain noindex
- legacy Hotel URLs remain 404

## Launch later
Go to **Hotel Intelligence → Launch Control**, tick **Enable public Hotel indexation**, save, purge LiteSpeed cache, then run H9 canonical/robots/sitemap regression.

Only hotels already passing the existing public-ready gate can become indexable.
