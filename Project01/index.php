<?php
include "db.php";

// Real numbers from the database (no made-up marketing figures).
$stats = db_one($conn,
    "SELECT
        (SELECT COUNT(*) FROM guards WHERE verification_status = 'approved') AS guards,
        (SELECT COUNT(DISTINCT location) FROM guards WHERE verification_status = 'approved') AS cities,
        (SELECT COUNT(*) FROM bookings WHERE status = 'confirmed') AS confirmed,
        (SELECT COUNT(*) FROM clients) AS clients") ?? [];

// Sample guard profiles shown on the landing page (the original showcase photos).
$featured = [
    ['full_name' => 'James', 'location' => 'Dhaka', 'experience' => 5, 'hourly_rate' => 20, 'specialties' => 'Event security, Night shift',
     'image' => 'https://img.freepik.com/free-photo/portrait-male-security-guard-with-uniform_23-2150368731.jpg'],
    ['full_name' => 'James', 'location' => 'Dhaka', 'experience' => 4, 'hourly_rate' => 20, 'specialties' => 'Corporate, Patrol',
     'image' => 'https://static.vecteezy.com/system/resources/thumbnails/039/892/385/small/ai-generated-a-security-guard-with-his-arms-crossed-on-the-sidewalk-free-photo.jpeg'],
    ['full_name' => 'James', 'location' => 'Dhaka', 'experience' => 6, 'hourly_rate' => 20, 'specialties' => 'Home security, CCTV',
     'image' => 'https://static.vecteezy.com/system/resources/thumbnails/039/892/247/small/ai-generated-a-security-guard-with-his-arms-crossed-on-the-sidewalk-free-photo.jpeg'],
    ['full_name' => 'James', 'location' => 'Dhaka', 'experience' => 3, 'hourly_rate' => 20, 'specialties' => 'Personal protection, Escort',
     'image' => 'https://img.freepik.com/premium-photo/security-guard-standing-confidently-front-modern-building-arms-crossed-with-light-solid-color-background_1081303-4476.jpg?semt=ais_hybrid&w=740&q=80'],
];

page_head('Professional Security On Demand', '', ['assets/landing.css']);
?>
<body>
    <nav class="lp-nav">
        <a class="brand" href="index.php">
            <img src="uploads/index/Image (12).jpg" alt="">
            <span>SECURITY PROVIDER</span>
        </a>
        <div class="lp-links">
            <a href="#guards">Guards</a>
            <a href="#how">How it works</a>
            <a href="#why">Why us</a>
        </div>
        <div class="lp-actions">
            <div class="dropdown">
                <button type="button" class="btn-outline" aria-haspopup="true">Sign in</button>
                <div class="menu">
                    <a href="Client/client_login.php"><?php echo icon('user'); ?><span>Client<small>Book and manage guards</small></span></a>
                    <a href="Guard/guard_login.php"><?php echo icon('badge'); ?><span>Guard<small>See your bookings</small></span></a>
                    <a href="Admin/admin_login.php"><?php echo icon('shield'); ?><span>Admin<small>Verify guards</small></span></a>
                </div>
            </div>
            <div class="dropdown">
                <button type="button" class="btn-solid" aria-haspopup="true">Get started</button>
                <div class="menu">
                    <a href="Client/client_register.php"><?php echo icon('user'); ?><span>I need security<small>Create a client account</small></span></a>
                    <a href="Guard/guard_register.php"><?php echo icon('badge'); ?><span>I am a guard<small>Apply to join</small></span></a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <section class="wrap hero">
            <div>
                <div class="eyebrow"><span class="dot"></span> Every guard is verified by our team</div>
                <h1>Professional security,<br><span class="gold-text">on demand.</span></h1>
                <p class="lead">Book trusted, verified security professionals for your home, business or event in minutes.
                    Compare experience and rates, book in one click, and get a confirmation from the guard.</p>
                <div class="hero-ctas">
                    <a class="btn-gold" style="width:auto" href="Client/client_register.php">Book a guard &rarr;</a>
                    <a class="btn-ghost" href="Guard/guard_register.php">Become a guard</a>
                </div>
                <div class="trust">
                    <span><?php echo icon('check'); ?> Admin-verified profiles</span>
                    <span><?php echo icon('lock'); ?> Secure accounts</span>
                    <span><?php echo icon('clock'); ?> Book in minutes</span>
                </div>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <div class="orbit o3"></div>
                <div class="orbit o2"></div>
                <div class="orbit o1"></div>
                <div class="shield"><img src="uploads/index/Image (12).jpg" alt=""></div>
                <div class="float-card fc1">
                    <div class="ic ic-green"><?php echo icon('check'); ?></div>
                    <div><strong>Booking confirmed</strong><small>Guard on duty tonight</small></div>
                </div>
                <div class="float-card fc2">
                    <div class="ic ic-gold"><?php echo icon('badge'); ?></div>
                    <div><strong>Verified guard</strong><small>Approved by an admin</small></div>
                </div>
                <div class="float-card fc3">
                    <div class="ic ic-blue"><?php echo icon('calendar'); ?></div>
                    <div><strong>Event security</strong><small>Home &middot; Business &middot; Personal</small></div>
                </div>
            </div>
        </section>

        <div class="wrap">
            <div class="stats-band">
                <div><b class="gold-text"><?php echo (int)($stats['guards'] ?? 0); ?></b><span>Verified guards</span></div>
                <div><b class="gold-text"><?php echo (int)($stats['cities'] ?? 0); ?></b><span>Areas covered</span></div>
                <div><b class="gold-text"><?php echo (int)($stats['confirmed'] ?? 0); ?></b><span>Confirmed bookings</span></div>
                <div><b class="gold-text"><?php echo (int)($stats['clients'] ?? 0); ?></b><span>Registered clients</span></div>
            </div>
        </div>

        <section class="section wrap" id="guards">
            <div class="section-title">
                <div class="kicker">Our guards</div>
                <h2>Meet some of our verified professionals</h2>
                <p>Every profile is reviewed by an admin before clients can see it. Sign in to view full profiles and book.</p>
            </div>
            <div class="lp-guards">
                <?php foreach ($featured as $guard): ?>
                    <?php
                        $specialties = array_filter(array_map('trim', explode(',', $guard['specialties'] ?? '')));
                    ?>
                    <article class="guard-card">
                        <div class="guard-image-container">
                            <img class="guard-image" src="<?php echo e($guard['image']); ?>" alt="<?php echo e($guard['full_name']); ?>" loading="lazy">
                        </div>
                        <span class="availability-badge">available</span>
                        <div class="guard-info">
                            <div class="guard-name"><?php echo e($guard['full_name']); ?></div>
                            <div class="guard-location"><?php echo e($guard['location']); ?> &middot; <?php echo (int)$guard['experience']; ?> yrs experience</div>
                            <div class="guard-specialties">
                                <?php foreach (array_slice($specialties, 0, 3) as $specialty): ?>
                                    <span class="specialty-tag"><?php echo e($specialty); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="guard-footer">
                                <div class="guard-price">$<?php echo number_format((float)$guard['hourly_rate'], 0); ?><span class="price-unit"> /hr</span></div>
                                <a class="view-profile-btn" href="Client/client_login.php">View profile</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section wrap" id="how">
            <div class="section-title">
                <div class="kicker">How it works</div>
                <h2>Safe in three simple steps</h2>
                <p>No phone calls or paperwork. Everything happens in your dashboard.</p>
            </div>
            <div class="steps-grid">
                <div class="step">
                    <div class="num">1</div>
                    <div class="big-ic"><?php echo icon('search'); ?></div>
                    <h3>Choose a guard</h3>
                    <p>Search verified guards by name, area or specialty, and compare experience and hourly rates.</p>
                </div>
                <div class="step">
                    <div class="num">2</div>
                    <div class="big-ic"><?php echo icon('calendar'); ?></div>
                    <h3>Book in one click</h3>
                    <p>Send a booking request and follow its status live: pending, confirmed or declined.</p>
                </div>
                <div class="step">
                    <div class="num">3</div>
                    <div class="big-ic"><?php echo icon('shield'); ?></div>
                    <h3>Stay secure</h3>
                    <p>Your guard confirms and arrives briefed and ready to protect what matters to you.</p>
                </div>
            </div>
        </section>

        <section class="section wrap" id="why">
            <div class="section-title">
                <div class="kicker">Why Security Provider</div>
                <h2>Built on trust, from sign-up to shift</h2>
            </div>
            <div class="feature-grid">
                <div class="feature">
                    <div class="f-ic"><?php echo icon('check'); ?></div>
                    <h3>Verified professionals</h3>
                    <p>Admins review every guard application before it goes live.</p>
                </div>
                <div class="feature">
                    <div class="f-ic"><?php echo icon('users'); ?></div>
                    <h3>For every need</h3>
                    <p>Homes, businesses, events and personal protection.</p>
                </div>
                <div class="feature">
                    <div class="f-ic"><?php echo icon('clock'); ?></div>
                    <h3>Clear hourly rates</h3>
                    <p>See each guard's rate and experience up front, with no hidden steps.</p>
                </div>
                <div class="feature">
                    <div class="f-ic"><?php echo icon('lock'); ?></div>
                    <h3>Secure by design</h3>
                    <p>Hashed passwords, protected forms and safe account recovery.</p>
                </div>
            </div>

            <div class="cta">
                <div>
                    <h2>Ready to feel safer?</h2>
                    <p>Create a free account and book your first guard today.</p>
                </div>
                <div class="cta-btns">
                    <a class="btn-gold btn-dark" style="width:auto" href="Client/client_register.php">Book a guard</a>
                    <a class="btn-gold btn-light" style="width:auto" href="Guard/guard_register.php">Join as a guard</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <div class="wrap">
            <span>&copy; <?php echo date('Y'); ?> Security Provider. All rights reserved.</span>
            <nav>
                <a href="#guards">Guards</a>
                <a href="#how">How it works</a>
                <a href="Client/client_login.php">Client login</a>
                <a href="Guard/guard_login.php">Guard login</a>
                <a href="Admin/admin_login.php">Admin</a>
            </nav>
        </div>
    </footer>
    <script>
        // Tap support for the Sign in / Get started menus (hover does not exist on phones).
        document.querySelectorAll('.dropdown > button').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                var menu = button.parentElement;
                document.querySelectorAll('.dropdown.open').forEach(function (d) { if (d !== menu) d.classList.remove('open'); });
                menu.classList.toggle('open');
            });
        });
        document.addEventListener('click', function () {
            document.querySelectorAll('.dropdown.open').forEach(function (d) { d.classList.remove('open'); });
        });
    </script>
</body>
</html>
