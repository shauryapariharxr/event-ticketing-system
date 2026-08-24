    </main><!-- /main.container -->

    <!-- ══════════════════════════════════════════════════
         SITE FOOTER  —  District by Zomato style
    ══════════════════════════════════════════════════ -->
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">

                <!-- Brand column -->
                <div class="col-12 col-md-4 col-lg-3">
                    <a href="<?= BASE_URL ?>/public/index.php" class="footer-brand-logo text-decoration-none">
                        <span class="f-aero">AERO</span><span class="f-tickets">TICKETS</span>
                    </a>
                    <p class="footer-tagline mt-2">
                        Your premium seat selection &amp; event booking platform. Real-time seating. Instant tickets.
                    </p>
                    <div class="footer-socials">
                        <a href="#" class="footer-social-btn" title="Twitter/X"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="footer-social-btn" title="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="footer-social-btn" title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="footer-social-btn" title="YouTube"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="col-6 col-md-2">
                    <p class="footer-col-title">Platform</p>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/public/index.php">Home</a></li>
                        <li><a href="<?= BASE_URL ?>/public/events.php">Browse Events</a></li>
                        <li><a href="<?= BASE_URL ?>/public/profile.php">My Tickets</a></li>
                        <li><a href="<?= BASE_URL ?>/public/validate_ticket.php">Gate Scan</a></li>
                        <li><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin Panel</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div class="col-6 col-md-2">
                    <p class="footer-col-title">Categories</p>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/public/events.php?q=concert">Concerts</a></li>
                        <li><a href="<?= BASE_URL ?>/public/events.php?q=comedy">Comedy Shows</a></li>
                        <li><a href="<?= BASE_URL ?>/public/events.php?q=theatre">Theatre</a></li>
                        <li><a href="<?= BASE_URL ?>/public/events.php?q=sports">Sports</a></li>
                        <li><a href="<?= BASE_URL ?>/public/events.php?q=festival">Festivals</a></li>
                    </ul>
                </div>

                <!-- Cities -->
                <div class="col-6 col-md-2">
                    <p class="footer-col-title">Cities</p>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/public/events.php?q=Pune">Pune</a></li>
                        <li><a href="<?= BASE_URL ?>/public/events.php?q=Mumbai">Mumbai</a></li>
                        <li><a href="<?= BASE_URL ?>/public/events.php?q=Delhi">Delhi</a></li>
                        <li><a href="<?= BASE_URL ?>/public/events.php?q=Bangalore">Bangalore</a></li>
                        <li><a href="<?= BASE_URL ?>/public/events.php?q=Hyderabad">Hyderabad</a></li>
                    </ul>
                </div>

                <!-- Help -->
                <div class="col-6 col-md-2">
                    <p class="footer-col-title">Help</p>
                    <ul class="footer-links">
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Refund Policy</a></li>
                        <li><a href="#">Booking Guide</a></li>
                        <li><a href="#">List Your Event</a></li>
                    </ul>
                </div>

            </div><!-- /row -->

            <hr class="footer-divider">

            <div class="footer-bottom">
                <p class="footer-copy mb-0">
                    &copy; <?= date('Y') ?> AeroTickets. All rights reserved. &nbsp;|&nbsp; A DBMS Project powered by PHP &amp; MySQL.
                </p>
                <div class="footer-legal">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms &amp; Conditions</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>

        </div><!-- /container -->
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
