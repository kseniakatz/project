<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

$title = 'Camagru';

ob_start();
?>
<section class="panel hero">
    <div>
        <p class="eyebrow">An analog photo quest</p>
        <h1 class="hero-title">Chase The Hidden Frame</h1>
        <p class="hero-subtitle">
            Camagru becomes a treasure log for strange portraits, secret routes, and cinematic snapshots.
            The visual direction borrows from the adventurous mood of the reference site: warm brass tones,
            heavy serif typography, layered panels, and a sense that every image is part of a larger map.
        </p>

        <div class="hero-actions">
            <a href="/register.php" class="button-link">Start Expedition</a>
            <a href="/login.php" class="button-secondary">Enter The Archive</a>
        </div>
    </div>

    <aside class="hero-card">
        <div>
            <p class="section-tag">Field Notes</p>
            <h2 class="hero-card-title">What This Style Means</h2>
        </div>

        <ul class="hero-card-list">
            <li>Large cinematic headlines instead of generic app typography.</li>
            <li>Deep ocean and brass colors instead of neutral dashboard tones.</li>
            <li>Framed sections that feel like collected pages from a captain's log.</li>
            <li>Landing copy written like an invitation to an expedition.</li>
        </ul>
    </aside>
</section>

<section class="panel section" id="story">
    <p class="section-tag">Story</p>
    <h2 class="section-title">Every Photo Needs A Legend</h2>
    <p class="section-copy">
        The reference design works because it treats content like myth, not inventory. This landing page uses
        the same approach: your app is framed as a place to collect scenes, clues, and discoveries rather than
        a plain upload form.
    </p>

    <div class="story-grid">
        <article class="story-card story-card-highlight">
            <p class="section-tag">Chapter One</p>
            <h3 class="hero-card-title">Build The Crew</h3>
            <p class="section-copy">
                Register, verify, and give each explorer a place in the archive. Authentication becomes part
                of the narrative instead of a detached utility page.
            </p>
        </article>

        <article class="story-card">
            <p class="section-tag">Chapter Two</p>
            <h3 class="hero-card-title">Collect Proof</h3>
            <p class="section-copy">
                Upload portraits, layers, and scenes as if they were fragments from a larger map. A gallery
                can later extend this same language without redesigning the shell again.
            </p>
        </article>
    </div>
</section>

<section class="panel section" id="expedition">
    <p class="section-tag">Expedition</p>
    <h2 class="section-title">Three Signals From The Reference</h2>

    <div class="section-grid">
        <article class="section-card">
            <strong class="metric">01</strong>
            <h3 class="hero-card-title">Scale</h3>
            <p class="section-copy">
                Oversized typography gives the page weight immediately. That is why the hero headline dominates
                the first screen instead of sitting inside a small card.
            </p>
        </article>

        <article class="section-card">
            <strong class="metric">02</strong>
            <h3 class="hero-card-title">Texture</h3>
            <p class="section-copy">
                The layered gradients, metallic borders, and soft highlights recreate the mood of a worn map
                without needing external image assets.
            </p>
        </article>

        <article class="section-card">
            <strong class="metric">03</strong>
            <h3 class="hero-card-title">Narrative</h3>
            <p class="section-copy">
                Sections are written like scenes in a voyage. That keeps the design from feeling like generic
                Tailwind scaffolding.
            </p>
        </article>
    </div>
</section>

<section class="panel section" id="gallery">
    <p class="section-tag">Gallery Readiness</p>
    <h2 class="section-title">Prepared For The Next Pass</h2>
    <div class="story-grid">
        <article class="story-card">
            <ul class="feature-list">
                <li>
                    <strong>Hero shell:</strong>
                    <span class="muted">establishes the reference mood across desktop and mobile.</span>
                </li>
                <li>
                    <strong>Shared auth styling:</strong>
                    <span class="muted">login and register now fit the same visual language.</span>
                </li>
                <li>
                    <strong>Expandable sections:</strong>
                    <span class="muted">easy to swap for real uploads, feed cards, or profile stats.</span>
                </li>
            </ul>
        </article>

        <article class="story-card story-card-highlight">
            <p class="section-copy">
                If you want, the next step is to push this further with an actual gallery wall, film-strip cards,
                or parchment-like upload panels. The current change gives you the base system to do that cleanly.
            </p>
            <div class="hero-actions">
                <a href="/register.php" class="button-link">Claim Your Spot</a>
                <a href="/login.php" class="button-secondary">Return To Crew</a>
            </div>
        </article>
    </div>
</section>
<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';
