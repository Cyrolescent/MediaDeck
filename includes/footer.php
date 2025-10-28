<style>
    /* Base Footer Styles */
    .main-footer {
        background-color: #11161dff;
        color: #E5E7EB;
        padding: 20px 40px;
        text-align: center;
        font-size: 14px;
        /* Removed border-top - this was the black outline */
    }

    .footer-copyright {
        margin-bottom: 5px;
    }

    .footer-copyright strong {
        color: #fff;
    }

    .footer-credits {
        font-size: 13px;
        color: #9CA3AF;
    }

    .footer-credits a {
        color: #4A90E2;
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-credits a:hover {
        color: #5BA3F5;
        text-decoration: underline;
    }

    /* Tablet Styles (768px - 1199px) */
    @media (max-width: 1199px) {
        .main-footer {
            padding: 18px 30px;
        }
    }

    /* Mobile Styles (<768px) */
    @media (max-width: 767px) {
        .main-footer {
            padding: 15px 20px;
        }

        .footer-copyright {
            margin-bottom: 8px;
            font-size: 13px;
        }

        .footer-credits {
            font-size: 12px;
            line-height: 1.6;
        }
    }

    /* Extra Small Mobile (<400px) */
    @media (max-width: 399px) {
        .main-footer {
            padding: 12px 15px;
            font-size: 12px;
        }

        .footer-copyright {
            font-size: 12px;
        }

        .footer-credits {
            font-size: 11px;
        }
    }
</style>

<footer class="main-footer">
    <div class="footer-copyright">
        <strong>MediaDeck</strong> © <?php echo date('Y'); ?>. All rights reserved.
    </div>
    <div class="footer-credits">
        Created by Group 9 - BSIT2-9 | 
        <a href="about.php">About us</a>
    </div>
</footer>