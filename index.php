<?php
/**
 * index.php — Home page
 * ------------------------------------------------------------
 * Sections (in order):
 *   1. Hero
 *   2. About (5 cards → modal)
 *   3. Education (timeline)
 *   4. Skills (tabbed grid + animated bars)
 *   5. Projects (featured + filter)
 *   6. Experience (timeline)
 *   7. Services (cards)
 *   8. Contact (form + info)
 */

require_once __DIR__ . '/includes/functions.php';
// Consume one-shot contact-success flag
$contactSent = !empty($_SESSION['contact_sent']);
unset($_SESSION['contact_sent']);

$pageTitle       = 'Home';
$pageDescription = 'Portfolio of Polycap Nyamongo Maturwe — Mathematics & Computer Studies Educator, Web Developer, and Technology Enthusiast based in Nairobi, Kenya.';

// ---- Fetch all data (single DB hit each) ----
$pdo = getDB();

$profile   = getProfile();
$socials   = getSocialLinks();
$education = $pdo->query('SELECT * FROM education ORDER BY display_order, start_year DESC')->fetchAll();
$skills    = $pdo->query('SELECT * FROM skills ORDER BY category, display_order, id')->fetchAll();
$projects  = $pdo->query('SELECT * FROM projects WHERE is_published = 1 ORDER BY is_featured DESC, display_order, year DESC')->fetchAll();
$experience= $pdo->query('SELECT * FROM experience ORDER BY display_order, start_date DESC')->fetchAll();
$services  = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY display_order, id')->fetchAll();

// Group skills by category (preserve order)
$skillGroups = [];
foreach ($skills as $s) {
    $skillGroups[$s['category']][] = $s;
}
$skillCategories = array_keys($skillGroups);

// Category list for project filter (only categories actually used)
$projCats = [];
foreach ($projects as $p) {
    $projCats[$p['category']] = true;
}
$projCats = array_keys($projCats);

// Counts for hero stats
$stats = [
    'projects'   => count($projects),
    'skills'     => count($skills),
    'services'   => count($services),
    'experience' => count($experience),
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<?php
// Prepare About modal content as JSON for JS
$aboutData = [
    'personal' => [
        'title' => 'Personal Details',
        'html'  => '<dl class="kv">'
                 . '<dt>Name</dt><dd>'        . e($profile['full_name'] ?? '') . '</dd>'
                 . '<dt>Location</dt><dd>'    . e($profile['location'] ?? '') . '</dd>'
                 . '<dt>Profession</dt><dd>'  . e($profile['profession'] ?? '') . '</dd>'
                 . '<dt>Nationality</dt><dd>' . e($profile['nationality'] ?? '') . '</dd>'
                 . '<dt>Languages</dt><dd>'   . e($profile['languages'] ?? '') . '</dd>'
                 . '</dl>'
    ],
    'profile' => [
        'title' => 'Professional Profile',
        'html'  => '<p>' . nl2br(e($profile['bio'] ?? '')) . '</p>'
    ],
    'interests' => [
        'title' => 'Career Interests',
        'html'  => '<p>' . nl2br(e($profile['career_interests'] ?? '')) . '</p>'
    ],
    'goals' => [
        'title' => 'Goals',
        'html'  => '<p>' . nl2br(e($profile['goals'] ?? '')) . '</p>'
    ],
    'hobbies' => [
        'title' => 'Hobbies & Interests',
        'html'  => '<p>' . nl2br(e($profile['hobbies'] ?? '')) . '</p>'
    ],
];
?>
<script>window.__ABOUT__ = <?= json_encode($aboutData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<main id="home">

<!-- =========================================================
     1. HERO
========================================================= -->
<section class="hero" id="hero">
  <div class="container hero__grid">

    <div class="hero__content">
      <span class="eyebrow" data-reveal>Available for work &amp; collaboration</span>

      <h1 class="hero__title" data-reveal data-reveal-delay="1">
        Hi, I'm <span class="gradient-text"><?= e($profile['full_name'] ?? APP_NAME) ?></span>
      </h1>

      <div class="hero__roles" data-reveal data-reveal-delay="2">
        <?php foreach (array_filter(array_map('trim', explode('|', $profile['professional_title'] ?? ''))) as $role): ?>
          <span><?= e($role) ?></span>
        <?php endforeach; ?>
      </div>

      <p class="hero__lead lead" data-reveal data-reveal-delay="2">
        <?= e($profile['short_intro'] ?? 'Welcome to my portfolio.') ?>
      </p>

      <div class="hero__ctas" data-reveal data-reveal-delay="3">
        <a href="<?= url('/#projects') ?>" class="btn btn--primary btn--lg">
          <i class="fas fa-diagram-project"></i> View My Projects
        </a>
        <a href="<?= url('/#contact') ?>" class="btn btn--outline btn--lg">
          <i class="fas fa-paper-plane"></i> Contact Me
        </a>
        <?php if (!empty($profile['cv_file'])): ?>
          <a href="<?= url($profile['cv_file']) ?>" class="btn btn--ghost btn--lg" download>
            <i class="fas fa-download"></i> Download CV
          </a>
        <?php else: ?>
          <a href="#" class="btn btn--ghost btn--lg" onclick="event.preventDefault();window.toast.show('info','CV coming soon — please contact me directly for now.')">
            <i class="fas fa-download"></i> Download CV
          </a>
        <?php endif; ?>
      </div>

      <?php if ($socials): ?>
      <ul class="hero__socials" data-reveal data-reveal-delay="4">
        <?php foreach ($socials as $s): ?>
          <li>
            <a href="<?= e($s['url']) ?>"
               target="_blank" rel="noopener noreferrer"
               aria-label="<?= e(ucfirst($s['platform'])) ?>"
               title="<?= e(ucfirst($s['platform'])) ?>">
              <i class="<?= e($s['icon_class'] ?: 'fas fa-link') ?>"></i>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <div class="hero__visual" data-reveal data-reveal-delay="2">
      <div class="profile-ring">
        <div class="profile-ring__inner">
          <?php
            $imgPath = !empty($profile['profile_image']) ? $profile['profile_image'] : null;
            $absImg  = $imgPath ? __DIR__ . '/' . $imgPath : null;
            $hasImg  = $imgPath && file_exists($absImg);
            $initials = '';
            foreach (preg_split('/\s+/', trim($profile['full_name'] ?? 'PN')) as $w) {
                if ($w !== '') $initials .= mb_strtoupper(mb_substr($w, 0, 1));
                if (mb_strlen($initials) >= 2) break;
            }
          ?>
          <?php if ($hasImg): ?>
            <img src="<?= url($imgPath) ?>" alt="<?= e($profile['full_name'] ?? 'Profile') ?>" loading="eager">
          <?php else: ?>
            <div class="profile-ring__placeholder"><?= e($initials ?: 'PN') ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="hero__badge hero__badge--tl">
        <i class="fas fa-code"></i> Web Developer
      </div>
      <div class="hero__badge hero__badge--br">
        <i class="fas fa-graduation-cap"></i> CS Educator
      </div>
    </div>
  </div>
</section>

<!-- =========================================================
     2. ABOUT
========================================================= -->
<section class="section section--alt" id="about">
  <div class="container">
    <header class="section__head" data-reveal>
      <span class="eyebrow">About Me</span>
      <h2>Getting to know the person <span class="gradient-text">behind the code</span></h2>
      <p>Click any card to read more. Everything here is editable from the admin panel.</p>
    </header>

    <div class="about__grid">
      <article class="about-card" data-reveal data-reveal-delay="1" data-about-open="personal">
        <div class="about-card__icon"><i class="fas fa-id-badge"></i></div>
        <h3>Personal Details</h3>
        <p><?= e($profile['location'] ?? '—') ?> · <?= e($profile['nationality'] ?? '—') ?></p>
        <span class="about-card__hint">Read more <i class="fas fa-arrow-right"></i></span>
      </article>

      <article class="about-card" data-reveal data-reveal-delay="2" data-about-open="profile">
        <div class="about-card__icon"><i class="fas fa-user-tie"></i></div>
        <h3>Professional Profile</h3>
        <p><?= e(excerpt($profile['bio'] ?? '', 90)) ?></p>
        <span class="about-card__hint">Read more <i class="fas fa-arrow-right"></i></span>
      </article>

      <article class="about-card" data-reveal data-reveal-delay="3" data-about-open="interests">
        <div class="about-card__icon"><i class="fas fa-lightbulb"></i></div>
        <h3>Career Interests</h3>
        <p><?= e(excerpt($profile['career_interests'] ?? '', 90)) ?></p>
        <span class="about-card__hint">Read more <i class="fas fa-arrow-right"></i></span>
      </article>

      <article class="about-card" data-reveal data-reveal-delay="4" data-about-open="goals">
        <div class="about-card__icon"><i class="fas fa-bullseye"></i></div>
        <h3>Goals</h3>
        <p><?= e(excerpt($profile['goals'] ?? '', 90)) ?></p>
        <span class="about-card__hint">Read more <i class="fas fa-arrow-right"></i></span>
      </article>

      <article class="about-card" data-reveal data-reveal-delay="4" data-about-open="hobbies">
        <div class="about-card__icon"><i class="fas fa-heart"></i></div>
        <h3>Hobbies &amp; Interests</h3>
        <p><?= e(excerpt($profile['hobbies'] ?? '', 90)) ?></p>
        <span class="about-card__hint">Read more <i class="fas fa-arrow-right"></i></span>
      </article>
    </div>
  </div>
</section>

<!-- About modal -->
<div class="modal" id="aboutModal" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="aboutModalTitle">
    <button class="modal__close" type="button" data-modal-close aria-label="Close">
      <i class="fas fa-times"></i>
    </button>
    <div class="modal__body">
      <h3 class="modal__title" id="aboutModalTitle">—</h3>
      <div class="modal__text" id="aboutModalBody"></div>
    </div>
  </div>
</div>

<!-- =========================================================
     3. EDUCATION
========================================================= -->
<section class="section" id="education">
  <div class="container">
    <header class="section__head section__head--center" data-reveal>
      <span class="eyebrow">Education</span>
      <h2>Academic <span class="gradient-text">background</span></h2>
      <p>My formal training in mathematics, computing, and IT.</p>
    </header>

    <?php if ($education): ?>
      <div class="timeline">
        <?php foreach ($education as $i => $ed): ?>
          <div class="timeline__item" data-reveal data-reveal-delay="<?= min(4, $i + 1) ?>">
            <span class="timeline__dot"><i class="fas <?= e($ed['institution_icon'] ?: 'fa-university') ?>"></i></span>
            <div class="timeline__card">
              <div class="timeline__meta">
                <span class="chip">
                  <i class="fas fa-calendar-alt"></i>
                  <?= e($ed['start_year']) ?><?= $ed['end_year'] ? ' – ' . e($ed['end_year']) : ' – Present' ?>
                </span>
                <?php if (!empty($ed['level'])): ?>
                  <span class="chip"><i class="fas fa-layer-group"></i> <?= e($ed['level']) ?></span>
                <?php endif; ?>
                <?php if (!empty($ed['certificate_url'])): ?>
                  <a class="chip" href="<?= e($ed['certificate_url']) ?>" target="_blank" rel="noopener">
                    <i class="fas fa-certificate"></i> Certificate
                  </a>
                <?php endif; ?>
              </div>
              <h3><?= e($ed['course'] ?: 'Programme') ?></h3>
              <span class="org"><?= e($ed['institution']) ?></span>
              <?php if (!empty($ed['description'])): ?>
                <p><?= e($ed['description']) ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><i class="fas fa-graduation-cap"></i>No education entries yet.</div>
    <?php endif; ?>
  </div>
</section>


<!-- =========================================================
     4. SKILLS
========================================================= -->
<section class="section section--alt" id="skills">
  <div class="container">
    <header class="section__head section__head--center" data-reveal>
      <span class="eyebrow">Skills</span>
      <h2>What I <span class="gradient-text">work with</span></h2>
      <p>Honest, editable levels — reflecting my current ability, not inflated claims.</p>
    </header>

    <?php if ($skillCategories): ?>
      <div class="skills__tabs" data-reveal>
        <?php foreach ($skillCategories as $i => $cat): ?>
          <button type="button"
                  class="tab<?= $i === 0 ? ' is-active' : '' ?>"
                  data-skill-tab="<?= e($cat) ?>">
            <?= e($cat) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="skills__grid" id="skillsGrid">
        <?php foreach ($skillGroups as $cat => $items): ?>
          <?php foreach ($items as $sk): ?>
            <div class="skill"
                 data-skill-cat="<?= e($cat) ?>"
                 data-level="<?= (int)$sk['level'] ?>"
                 data-reveal
                 style="display: <?= $cat === $skillCategories[0] ? '' : 'none' ?>;">
              <div class="skill__row">
                <span class="skill__icon"><i class="<?= e($sk['icon_class'] ?: 'fas fa-code') ?>"></i></span>
                <span class="skill__name"><?= e($sk['name']) ?></span>
                <span class="skill__level"><?= (int)$sk['level'] ?>%</span>
              </div>
              <div class="skill__bar" aria-hidden="true"><span></span></div>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><i class="fas fa-code"></i>No skills added yet.</div>
    <?php endif; ?>
  </div>
</section>

<!-- =========================================================
     5. PROJECTS
========================================================= -->
<section class="section" id="projects">
  <div class="container">
    <header class="section__head section__head--center" data-reveal>
      <span class="eyebrow">Portfolio</span>
      <h2>Selected <span class="gradient-text">projects</span></h2>
      <p>Click a card to see the full details. Use the filters to explore by category.</p>
    </header>

    <?php if ($projects): ?>
      <div class="projects__filters" data-project-filter data-reveal>
        <button type="button" class="tab is-active" data-filter="all">All</button>
        <?php foreach ($projCats as $cat): ?>
          <button type="button" class="tab" data-filter="<?= e($cat) ?>"><?= e($cat) ?></button>
        <?php endforeach; ?>
      </div>

      <div class="projects__grid">
        <?php foreach ($projects as $i => $p):
          $imgPath = !empty($p['image']) ? $p['image'] : null;
          $hasImg  = $imgPath && file_exists(__DIR__ . '/' . $imgPath);
          $tech    = techToArray($p['technologies'] ?? '');
        ?>
          <article class="project-card"
                   data-reveal data-reveal-delay="<?= min(4, $i % 4 + 1) ?>"
                   data-project-category="<?= e($p['category']) ?>">

            <div class="project-card__media">
              <?php if ($hasImg): ?>
                <img src="<?= url($imgPath) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
              <?php else: ?>
                <div style="width:100%;height:100%;display:grid;place-items:center;color:var(--muted);font-family:var(--font-mono);font-size:.85rem;">
                  <?= e(strtoupper($p['category'])) ?>
                </div>
              <?php endif; ?>
              <div class="project-card__badges">
                <?php if (!empty($p['is_featured'])): ?>
                  <span class="badge badge--featured"><i class="fas fa-star"></i> Featured</span>
                <?php endif; ?>
                <?php if (!empty($p['year'])): ?>
                  <span class="badge badge--year"><?= e($p['year']) ?></span>
                <?php endif; ?>
              </div>
            </div>

            <div class="project-card__body">
              <div class="project-card__cat"><?= e($p['category']) ?></div>
              <h3 class="project-card__title"><?= e($p['name']) ?></h3>
              <p class="project-card__desc"><?= e(excerpt($p['short_description'] ?? '', 130)) ?></p>

              <?php if ($tech): ?>
                <ul class="project-card__tech">
                  <?php foreach (array_slice($tech, 0, 5) as $t): ?>
                    <li><?= e($t) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>

              <div class="project-card__actions">
                <button type="button"
                        class="btn btn--primary btn--sm"
                        data-open-project
                        data-name="<?= e($p['name']) ?>"
                        data-category="<?= e($p['category']) ?>"
                        data-year="<?= e($p['year']) ?>"
                        data-description="<?= e($p['full_description'] ?? $p['short_description'] ?? '') ?>"
                        data-tech="<?= e($p['technologies'] ?? '') ?>"
                        data-image="<?= $hasImg ? e(url($imgPath)) : '' ?>"
                        data-github="<?= e($p['github_url'] ?? '') ?>"
                        data-live="<?= e($p['live_url'] ?? '') ?>"
                        data-docs="<?= e($p['documentation_url'] ?? '') ?>">
                  <i class="fas fa-eye"></i> View Project
                </button>
                <?php if (!empty($p['github_url'])): ?>
                  <a href="<?= e($p['github_url']) ?>" target="_blank" rel="noopener" class="btn btn--outline btn--sm">
                    <i class="fab fa-github"></i> GitHub
                  </a>
                <?php endif; ?>
                <?php if (!empty($p['live_url'])): ?>
                  <a href="<?= e($p['live_url']) ?>" target="_blank" rel="noopener" class="btn btn--ghost btn--sm">
                    <i class="fas fa-external-link-alt"></i> Live Demo
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="empty-state" data-project-empty style="display:none;margin-top:1.5rem;">
        <i class="fas fa-filter"></i>No projects match this category.
      </div>
    <?php else: ?>
      <div class="empty-state"><i class="fas fa-diagram-project"></i>No projects added yet.</div>
    <?php endif; ?>
  </div>
</section>

<!-- Project modal -->
<div class="modal" id="projectModal" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="projectModalTitle">
    <button class="modal__close" type="button" data-modal-close aria-label="Close">
      <i class="fas fa-times"></i>
    </button>
    <div class="modal__media" data-modal-media>
      <img src="" alt="" data-modal-image>
    </div>
    <div class="modal__body">
      <h3 class="modal__title" id="projectModalTitle" data-modal-name>—</h3>
      <div class="modal__meta">
        <span data-modal-category></span>
        <span data-modal-year></span>
      </div>
      <p class="modal__text" data-modal-description></p>
      <ul class="modal__tech" data-modal-tech></ul>
      <div class="modal__actions">
        <a href="#" class="btn btn--outline btn--sm" data-modal-github target="_blank" rel="noopener">
          <i class="fab fa-github"></i> GitHub
        </a>
        <a href="#" class="btn btn--primary btn--sm" data-modal-live target="_blank" rel="noopener">
          <i class="fas fa-external-link-alt"></i> Live Demo
        </a>
        <a href="#" class="btn btn--ghost btn--sm" data-modal-docs target="_blank" rel="noopener">
          <i class="fas fa-book"></i> Documentation
        </a>
      </div>
    </div>
  </div>
</div>

<!-- =========================================================
     6. EXPERIENCE
========================================================= -->
<section class="section section--alt" id="experience">
  <div class="container">
    <header class="section__head" data-reveal>
      <span class="eyebrow">Experience</span>
      <h2>Where I've <span class="gradient-text">worked &amp; taught</span></h2>
      <p>Teaching, mentoring, and building real projects.</p>
    </header>

    <?php if ($experience): ?>
      <div class="exp-grid">
        <?php foreach ($experience as $i => $ex):
          $resp = linesToArray($ex['responsibilities'] ?? '');
          $ach  = linesToArray($ex['achievements'] ?? '');
        ?>
          <article class="exp-item" data-reveal data-reveal-delay="<?= min(4, $i + 1) ?>">
            <div class="exp-item__date">
              <?= e(formatDateRange($ex['start_date'], $ex['end_date'], 'M Y')) ?>
            </div>
            <div>
              <h3 class="exp-item__role"><?= e($ex['position']) ?></h3>
              <div class="exp-item__org">
                <i class="fas fa-building"></i> <?= e($ex['organization']) ?>
              </div>
              <?php if (!empty($ex['description'])): ?>
                <p><?= e($ex['description']) ?></p>
              <?php endif; ?>
              <?php if ($resp): ?>
                <h4>Responsibilities</h4>
                <ul><?php foreach ($resp as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul>
              <?php endif; ?>
              <?php if ($ach): ?>
                <h4>Achievements</h4>
                <ul><?php foreach ($ach as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><i class="fas fa-briefcase"></i>No experience entries yet.</div>
    <?php endif; ?>
  </div>
</section>

<!-- =========================================================
     7. SERVICES
========================================================= -->
<section class="section" id="services">
  <div class="container">
    <header class="section__head section__head--center" data-reveal>
      <span class="eyebrow">Services</span>
      <h2>How I can <span class="gradient-text">help you</span></h2>
      <p>Websites, systems, databases, and tech support — built with care.</p>
    </header>

    <?php if ($services): ?>
      <div class="services__grid">
        <?php foreach ($services as $i => $sv): ?>
          <article class="service" data-reveal data-reveal-delay="<?= min(4, $i % 4 + 1) ?>">
            <div class="service__icon"><i class="<?= e($sv['icon_class'] ?: 'fas fa-cogs') ?>"></i></div>
            <h3><?= e($sv['title']) ?></h3>
            <p><?= e($sv['description'] ?? '') ?></p>
            <a class="service__cta" href="<?= url('/#contact') ?>">
              Request service <i class="fas fa-arrow-right"></i>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><i class="fas fa-cogs"></i>No services listed yet.</div>
    <?php endif; ?>
  </div>
</section>


<!-- =========================================================
     8. CONTACT
========================================================= -->
<section class="section section--alt" id="contact">
  <div class="container">
    <header class="section__head section__head--center" data-reveal>
      <span class="eyebrow">Contact</span>
      <h2>Let's build something <span class="gradient-text">together</span></h2>
      <p>Have a project, question, or opportunity? Send me a message and I'll get back to you.</p>
    </header>

    <div class="contact__grid">

      <!-- Contact info -->
      <div class="contact__info" data-reveal>
        <?php if (!empty($profile['email'])): ?>
          <a class="contact__row" href="mailto:<?= e($profile['email']) ?>">
            <i class="fas fa-envelope"></i>
            <div>
              <div class="label">Email</div>
              <div class="value"><?= e($profile['email']) ?></div>
            </div>
          </a>
        <?php endif; ?>

        <?php if (!empty($profile['phone'])): ?>
          <a class="contact__row" href="tel:<?= e(preg_replace('/\s+/', '', $profile['phone'])) ?>">
            <i class="fas fa-phone"></i>
            <div>
              <div class="label">Phone</div>
              <div class="value"><?= e($profile['phone']) ?></div>
            </div>
          </a>
        <?php endif; ?>

        <?php if (!empty($profile['whatsapp'])): ?>
          <?php $waNum = preg_replace('/\D+/', '', $profile['whatsapp']); ?>
          <a class="contact__row" href="https://wa.me/<?= e($waNum) ?>" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp"></i>
            <div>
              <div class="label">WhatsApp</div>
              <div class="value"><?= e($profile['whatsapp']) ?></div>
            </div>
          </a>
        <?php endif; ?>

        <?php if (!empty($profile['location'])): ?>
          <div class="contact__row">
            <i class="fas fa-map-marker-alt"></i>
            <div>
              <div class="label">Location</div>
              <div class="value"><?= e($profile['location']) ?></div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($socials): ?>
          <div class="contact__row">
            <i class="fas fa-share-alt"></i>
            <div>
              <div class="label">Find me online</div>
              <div class="value" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.35rem;">
                <?php foreach ($socials as $s): ?>
                  <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener"
                     class="chip" title="<?= e(ucfirst($s['platform'])) ?>">
                    <i class="<?= e($s['icon_class'] ?: 'fas fa-link') ?>"></i>
                    <?= e(ucfirst($s['platform'])) ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Contact form -->
       <?php if ($contactSent): ?>
<div class="contact-form contact-form--success">
    <div class="empty-state" style="border-style:solid;border-color:#10b981;background:color-mix(in srgb,#10b981 6%, var(--bg-elev));">
        <i class="fas fa-circle-check" style="color:#10b981;"></i>
        <h3 style="margin-bottom:.5rem;color:var(--text);">Message sent!</h3>
        <p>Thanks for reaching out. I'll get back to you within 24 hours.</p>
        <a href="<?= url('/#contact') ?>" class="btn btn--ghost btn--sm" style="margin-top:1rem;">
            <i class="fas fa-rotate-right"></i> Send another message
        </a>
    </div>
</div>
<?php else: ?>
      <form class="contact-form"
            method="post"
            action="<?= url('/contact.php') ?>"
            data-contact-form
            novalidate>
        <?= csrfField() ?>

        <div class="form-row">
          <div class="form-group">
            <label for="cf-name">Your Name</label>
            <input type="text" id="cf-name" name="name" class="form-control"
                   placeholder="Jane Doe" value="<?= e(old('name')) ?>"
                   data-required maxlength="120" autocomplete="name">
            <div class="field-error"></div>
          </div>
          <div class="form-group">
            <label for="cf-email">Email Address</label>
            <input type="email" id="cf-email" name="email" class="form-control"
                   placeholder="jane@example.com" value="<?= e(old('email')) ?>"
                   data-required maxlength="120" autocomplete="email">
            <div class="field-error"></div>
          </div>
        </div>

        <div class="form-group">
          <label for="cf-subject">Subject</label>
          <input type="text" id="cf-subject" name="subject" class="form-control"
                 placeholder="How can I help?" value="<?= e(old('subject')) ?>"
                 data-required maxlength="180">
          <div class="field-error"></div>
        </div>

        <div class="form-group">
          <label for="cf-message">Message</label>
          <textarea id="cf-message" name="message" class="form-control"
                    placeholder="Tell me a bit about your project or question…"
                    data-required maxlength="5000"><?= e(old('message')) ?></textarea>
          <div class="field-error"></div>
          <div class="form-hint">Max 5000 characters. I usually reply within 24 hours.</div>
        </div>

        <button type="submit" class="btn btn--primary btn--lg btn--block">
          <i class="fas fa-paper-plane"></i> Send Message
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
