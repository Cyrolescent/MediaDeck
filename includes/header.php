<style>
    /* Base Header Styles */
    .main-header {
        background-color: #1F2937;
        color: white;
        height: 70px;
        padding: 0 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        width: 100%;
        box-sizing: border-box;
    }

    .logo-container {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .logo-container a {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }

    .header-logo {
        width: 85px;
        height: 85px;
        margin-top: 5px;
        display: block;
    }

    .header-title {
        width: 180px;
        height: 85px;
        display: block;
    }

    .main-nav {
        display: flex;
        gap: 25px;
        align-items: center;
        flex-shrink: 0;
        margin-left: auto;
    }

    .main-nav a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        background: none;
        margin: 0;
        padding: 8px 12px;
        transition: background-color 0.3s;
        border-radius: 4px;
        white-space: nowrap;
    }

    .main-nav a:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Tablet Styles (768px - 1199px) */
    @media (max-width: 1199px) {
        .main-header {
            padding: 0 30px;
        }

        .header-logo {
            width: 70px;
            height: 70px;
        }

        .header-title {
            width: 150px;
            height: 70px;
        }

        .main-nav {
            gap: 20px;
        }

        .main-nav a {
            font-size: 15px;
            padding: 6px 10px;
        }
    }

    /* Small Tablet / Large Mobile (600px - 767px) */
    @media (max-width: 767px) {
        .main-header {
            padding: 0 20px;
            height: 60px;
        }

        .header-logo {
            width: 60px;
            height: 60px;
            margin-top: 0;
        }

        .header-title {
            display: none; /* Hide title logo */
        }

        .main-nav {
            gap: 15px;
        }

        .main-nav a {
            font-size: 14px;
            padding: 6px 10px;
        }
    }

    /* Mobile Styles (<600px) */
    @media (max-width: 599px) {
        .main-header {
            padding: 0 15px;
            height: 55px;
        }

        .logo-container {
            position: absolute;
            left: 15px;
        }

        .header-logo {
            width: 45px;
            height: 45px;
        }

        .main-nav {
            gap: 12px;
            margin-left: auto;
            margin-right: 0;
        }

        .main-nav a {
            font-size: 13px;
            padding: 5px 8px;
        }
    }

    /* Extra Small Mobile (<400px) */
    @media (max-width: 399px) {
        .main-header {
            padding: 0 10px;
            height: 50px;
        }

        .logo-container {
            left: 10px;
        }

        .header-logo {
            width: 40px;
            height: 40px;
        }

        .main-nav {
            gap: 8px;
            margin-left: auto;
            margin-right: 0;
        }

        .main-nav a {
            font-size: 12px;
            padding: 4px 6px;
        }
    }
</style>

<header class="main-header">
    <div class="logo-container">
        <a href="index.php">
            <img src="assets/images/headerlogo.png" alt="MediaDeck Logo" class="header-logo">
            <img src="assets/images/title.png" alt="MediaDeck Title" class="header-title">
        </a>
    </div>
    <nav class="main-nav">
        <a href="view_media.php">View</a>
        <a href="settings.php">Settings</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>