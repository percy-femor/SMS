<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FISC-School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5; /* Indigo 600 */
            --primary-dark: #4338ca; /* Indigo 700 */
            --secondary: #8b5cf6; /* Violet 500 */
            --accent: #f59e0b; /* Amber 500 */
            --text: #1f2937; /* Gray 800 */
            --text-light: #6b7280; /* Gray 500 */
            --white: #ffffff;
            --gray-light: #f9fafb;
            --glass: rgba(255, 255, 255, 0.9);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 1rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }

        body {
            line-height: 1.6;
            color: var(--text);
            background-color: var(--gray-light);
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header & Navigation */
        header {
            background-color: var(--glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 0;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .logo i {
            font-size: 1.75rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            transition: color 0.3s;
            font-size: 0.95rem;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            border: none;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(79, 70, 229, 0.3);
        }

        .mobile-menu-btn {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text);
            background: none;
            border: none;
        }

        /* Hero Section */
        .hero {
            padding: 6rem 0;
            position: relative;
            overflow: hidden;
            background: radial-gradient(circle at top right, rgba(139, 92, 246, 0.1), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(79, 70, 229, 0.1), transparent 40%);
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            color: var(--text);
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text p {
            font-size: 1.125rem;
            color: var(--text-light);
            margin-bottom: 2.5rem;
            max-width: 540px;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
        }

        .hero-image {
            position: relative;
        }

        .hero-image img {
            width: 100%;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            transform: perspective(1000px) rotateY(-5deg);
            transition: transform 0.5s ease;
        }

        .hero-image:hover img {
            transform: perspective(1000px) rotateY(0deg);
        }

        /* Features Section */
        .features {
            padding: 6rem 0;
            background-color: var(--white);
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .section-header p {
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .feature-icon {
            width: 3rem;
            height: 3rem;
            background: rgba(79, 70, 229, 0.1);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .feature-card p {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* Payment Plans Section */
        .pricing {
            padding: 6rem 0;
            background-color: var(--gray-light);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .pricing-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .pricing-card.popular {
            border: 2px solid var(--primary);
            transform: scale(1.05);
            z-index: 1;
        }

        .popular-badge {
            background: var(--primary);
            color: var(--white);
            padding: 0.25rem 1rem;
            position: absolute;
            top: 0;
            right: 0;
            border-bottom-left-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .plan-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .plan-price {
            font-size: 3rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 2rem;
        }

        .plan-price span {
            font-size: 1rem;
            color: var(--text-light);
            font-weight: 400;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 2.5rem;
            flex-grow: 1;
        }

        .plan-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .plan-features li i {
            color: var(--primary);
        }

        .pricing-card .btn {
            width: 100%;
        }

        /* Testimonials */
        .testimonials {
            padding: 6rem 0;
            background-color: var(--white);
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .testimonial-card {
            background: var(--gray-light);
            padding: 2rem;
            border-radius: var(--radius);
            position: relative;
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 1.5rem;
            color: var(--text);
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 3rem;
            height: 3rem;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
        }

        /* Footer */
        footer {
            background-color: #111827;
            color: #9ca3af;
            padding: 4rem 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-col h3 {
            color: var(--white);
            font-size: 1.125rem;
            margin-bottom: 1.5rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--white);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            color: #9ca3af;
            font-size: 1.25rem;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: var(--white);
        }

        .footer-bottom {
            border-top: 1px solid #374151;
            padding-top: 2rem;
            text-align: center;
            font-size: 0.875rem;
        }

        /* Mobile Menu */
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            height: 100vh;
            background: var(--white);
            z-index: 999;
            transition: right 0.3s ease;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .mobile-nav.active {
            right: 0;
        }

        .mobile-nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-menu {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text);
        }

        .mobile-links {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .mobile-links a {
            font-size: 1.25rem;
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 992px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-text p {
                margin: 0 auto 2.5rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .pricing-card.popular {
                transform: scale(1);
            }
        }

        @media (max-width: 768px) {
            .nav-links, .auth-buttons {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <a href="#" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    FISC-Manage
                </a>
                <div class="nav-links">
                    <a href="#home">Home</a>
                    <a href="#features">Features</a>
                    <a href="#pricing">Pricing</a>
                    <a href="#testimonials">Testimonials</a>
                </div>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-outline">Log In</a>
                    <a href="#" class="btn btn-primary">Get Started</a>
                </div>
                <button class="mobile-menu-btn" id="menuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <div class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-header">
            <a href="#" class="logo">FISC-Manage</a>
            <i class="fas fa-times close-menu" id="closeBtn"></i>
        </div>
        <div class="mobile-links">
            <a href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="#pricing">Pricing</a>
            <a href="#testimonials">Testimonials</a>
            <hr>
            <a href="login.php">Log In</a>
            <a href="#" style="color: var(--primary);">Get Started</a>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text fade-in">
                    <h1>Modern School Management Simplified</h1>
                    <p>Streamline operations, enhance learning, and connect your entire school community with one powerful, easy-to-use platform.</p>
                    <div class="hero-buttons">
                        <a href="login.php" class="btn btn-primary">Start Free Trial</a>
                        <a href="#features" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
                <div class="hero-image fade-in">
                    <img src="https://images.unsplash.com/photo-1531403009284-440f080d1e12?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="Dashboard Preview">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header fade-in">
                <h2>Everything You Need</h2>
                <p>Comprehensive tools designed to make school administration effortless and efficient.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Student Information</h3>
                    <p>Centralized database for student records, attendance tracking, and academic history.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3>Teacher Portal</h3>
                    <p>Tools for lesson planning, grading, and seamless communication with students.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Smart Scheduling</h3>
                    <p>Automated timetable generation and event management for the entire academic year.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3>Fee Management</h3>
                    <p>Track payments, generate invoices, and manage financial records securely.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3>Analytics & Reports</h3>
                    <p>Insightful dashboards and customizable reports to track school performance.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Communication</h3>
                    <p>Integrated messaging system for staff, parents, and students to stay connected.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Payment Plans Section -->
    <section class="pricing" id="pricing">
        <div class="container">
            <div class="section-header fade-in">
                <h2>Simple, Transparent Pricing</h2>
                <p>Choose the plan that best fits your institution's needs. No hidden fees.</p>
            </div>
            <div class="pricing-grid">
                <!-- Basic Plan -->
                <div class="pricing-card fade-in">
                    <h3 class="plan-name">Basic</h3>
                    <div class="plan-price">$29<span>/mo</span></div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Up to 200 Students</li>
                        <li><i class="fas fa-check"></i> Basic Reporting</li>
                        <li><i class="fas fa-check"></i> Attendance Tracking</li>
                        <li><i class="fas fa-check"></i> Email Support</li>
                    </ul>
                    <a href="#" class="btn btn-outline">Choose Basic</a>
                </div>

                <!-- Pro Plan -->
                <div class="pricing-card popular fade-in">
                    <div class="popular-badge">Most Popular</div>
                    <h3 class="plan-name">Professional</h3>
                    <div class="plan-price">$79<span>/mo</span></div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Up to 1000 Students</li>
                        <li><i class="fas fa-check"></i> Advanced Analytics</li>
                        <li><i class="fas fa-check"></i> Parent Portal</li>
                        <li><i class="fas fa-check"></i> Fee Management</li>
                        <li><i class="fas fa-check"></i> Priority Support</li>
                    </ul>
                    <a href="#" class="btn btn-primary">Choose Pro</a>
                </div>

                <!-- Enterprise Plan -->
                <div class="pricing-card fade-in">
                    <h3 class="plan-name">Enterprise</h3>
                    <div class="plan-price">$199<span>/mo</span></div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Unlimited Students</li>
                        <li><i class="fas fa-check"></i> Custom Features</li>
                        <li><i class="fas fa-check"></i> API Access</li>
                        <li><i class="fas fa-check"></i> Dedicated Account Manager</li>
                        <li><i class="fas fa-check"></i> 24/7 Phone Support</li>
                    </ul>
                    <a href="#" class="btn btn-outline">Contact Sales</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-header fade-in">
                <h2>Trusted by Educators</h2>
                <p>Hear what school administrators and teachers have to say about FISC-Manage.</p>
            </div>
            <div class="testimonial-grid">
                <div class="testimonial-card fade-in">
                    <p class="testimonial-text">"The interface is incredibly intuitive. We were able to train our entire staff in just one afternoon. It's been a game-changer for our administration."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">RC</div>
                        <div>
                            <h4>Robert Chen</h4>
                            <small>Principal, Lincoln High</small>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card fade-in">
                    <p class="testimonial-text">"Finally, a system that handles grading and attendance without the headache. The parent portal has also significantly improved our community engagement."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">SM</div>
                        <div>
                            <h4>Sarah Mitchell</h4>
                            <small>Teacher, Westwood Middle</small>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card fade-in">
                    <p class="testimonial-text">"The support team is fantastic. Any time we've had a question, they've been there to help immediately. Highly recommended for any growing school."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">JD</div>
                        <div>
                            <h4>James Davis</h4>
                            <small>IT Director, Oak District</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>FISC-Manage</h3>
                    <p>Empowering education through technology. We build tools that help schools focus on what matters most - teaching and learning.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Product</h3>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                        <li><a href="#">Security</a></li>
                        <li><a href="#">Updates</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Support</h3>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Status</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Legal</h3>
                    <ul class="footer-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 FISC-Manage. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        const menuBtn = document.getElementById('menuBtn');
        const closeBtn = document.getElementById('closeBtn');
        const mobileNav = document.getElementById('mobileNav');
        const mobileLinks = document.querySelectorAll('.mobile-links a');

        function toggleMenu() {
            mobileNav.classList.toggle('active');
        }

        menuBtn.addEventListener('click', toggleMenu);
        closeBtn.addEventListener('click', toggleMenu);

        // Close menu when clicking a link
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileNav.classList.remove('active');
            });
        });

        // Scroll Animations
        const observerOptions = {
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>

</html>