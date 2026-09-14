# Server Turizm Homepage Intelligence v0.3.3

H13C Journey Evidence / Digital PR homepage module on top of the v0.3.2 H12F composition baseline.

- Preserves v0.3.1 full-bleed behavior.
- Preserves v0.3.2 H12F fail-safe composition and cutover safety.
- Adds a dynamic Journey Evidence section sourced from a published WordPress Post.
- Default source Post: #6216 (Özbekistan / İmam Buhârî Merkezi).
- Reads title, excerpt, featured image and permalink directly from WordPress; no duplicated article data.
- Preview is enabled by default; public exposure is controlled by a separate STORY gate and defaults OFF on upgrade.
- Uses H2/H3 only; does not create additional H1.
- Adds no new schema and does not touch canonical, public robots, sitemap or Organization schema ownership.
- Internal CTA points to the canonical Server Turizm article, not directly to the external source.
- Mobile stacks image → text and preserves no-horizontal-overflow behavior.
