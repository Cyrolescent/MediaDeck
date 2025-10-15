<?php
require_once __DIR__ . '/config/dbconfig.php';

// Fetch all media
$sql = "SELECT id, title, type, storage_type, file_path, rating, is_favorite, notes, created_at FROM media ORDER BY created_at DESC";
$result = $conn->query($sql);

// Organize media by type
$images = [];
$videos = [];
$audio = [];
$documents = [];
$allMediaArray = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allMediaArray[] = $row;
        switch ($row['type']) {
            case 'image':
                $images[] = $row;
                break;
            case 'video':
                $videos[] = $row;
                break;
            case 'audio':
                $audio[] = $row;
                break;
            case 'text':
                $documents[] = $row;
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Media - MediaDeck</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #F3F4F6;
        }

        .header-controls {
            background: white;
            padding: 20px 30px;
            display: flex;
            gap: 20px;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .search-box {
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="%23999"><path d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"/></svg>') no-repeat 12px center;
            padding-left: 40px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #4A90E2;
            box-shadow: 0 0 5px rgba(74, 144, 226, 0.3);
        }

        .control-buttons {
            display: flex;
            gap: 10px;
        }

        button {
            padding: 10px 20px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            color: #333;
            transition: all 0.2s;
        }

        button:hover {
            background: #f5f5f5;
            border-color: #4A90E2;
            color: #4A90E2;
        }

        .container {
            padding: 0 30px;
        }

        .section {
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .scroll-container {
            position: relative;
            overflow: hidden;
        }

        .scroll-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(74, 144, 226, 0.9);
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            z-index: 10;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .scroll-arrow:hover {
            background: rgba(74, 144, 226, 1);
            transform: translateY(-50%) scale(1.1);
        }

        .scroll-arrow.left {
            left: 10px;
        }

        .scroll-arrow.right {
            right: 10px;
        }

        .items-wrapper {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            gap: 20px;
            padding: 10px 0;
            scroll-snap-type: x mandatory;
            align-items: flex-start;
        }

        /* IMAGES & VIDEOS */
        .media-item-square {
            flex: 0 0 200px;
            aspect-ratio: 1;
            background: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            scroll-snap-align: start;
            transition: transform 0.2s;
            cursor: pointer;
            position: relative;
        }

        .media-item-square:hover {
            transform: scale(1.05);
        }

        .media-item-square img {
            width: 100%;
            height: 80%;
            object-fit: cover;
        }

        .media-item-square .title {
            height: 20%;
            padding: 8px;
            background: white;
            font-size: 13px;
            font-weight: bold;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }

        .media-item-square .placeholder {
            width: 100%;
            height: 80%;
            background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        /* AUDIO */
        .media-item-circle {
            flex: 0 0 140px;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            scroll-snap-align: start;
            transition: transform 0.2s;
            cursor: pointer;
            position: relative;
        }

        .media-item-circle:hover {
            transform: scale(1.05);
        }

        .media-item-circle img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .audio-item {
            text-align: center;
        }

        .audio-item .title {
            margin-top: 8px;
            font-size: 12px;
            font-weight: bold;
            color: #333;
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* DOCUMENTS */
        .media-item-portrait {
            flex: 0 0 150px;
            height: 200px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            scroll-snap-align: start;
            transition: transform 0.2s;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .media-item-portrait:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .media-item-portrait .placeholder {
            width: 100%;
            height: 70%;
            background: linear-gradient(135deg, #e8e8e8 0%, #f5f5f5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .media-item-portrait img {
            width: 100%;
            height: 70%;
            object-fit: cover;
        }

        .media-item-portrait .title {
            width: 100%;
            height: 30%;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            color: #333;
            text-align: center;
            word-wrap: break-word;
            overflow: hidden;
        }

        .items-wrapper::-webkit-scrollbar {
            height: 6px;
        }

        .items-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .items-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .items-wrapper::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .no-items {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-size: 14px;
        }

        /* SEARCH RESULTS */
        .search-result-card {
            text-decoration: none;
        }

        #searchResults {
            display: none;
            margin-bottom: 50px;
        }

        #resultCounter {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
            font-weight: bold;
        }

        #searchGrid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        /* VIEW OPTIONS & STYLING */
        .favorite-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            width: 24px;
            height: 24px;
            background: rgba(255, 0, 0, 0.8);
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5;
        }

        .favorite-badge.show {
            display: flex;
        }

        .rating-badge {
            position: absolute;
            bottom: 28px;
            right: 8px;
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            display: none;
            align-items: center;
            gap: 2px;
            z-index: 5;
        }

        .rating-badge.show {
            display: flex;
        }

        .media-item-square.border-orange { border: 3px solid #FF8C00; }
        .media-item-square.border-blue { border: 3px solid #4A90E2; }
        .media-item-circle.border-green { border: 3px solid #4CAF50; }
        .media-item-portrait.border-grey { border: 3px solid #9E9E9E; }

        /* SIDEBAR POPUPS */
        .sidebar-popup {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.2);
            transition: right 0.3s;
            z-index: 1000;
            overflow-y: auto;
            padding: 30px;
        }

        .sidebar-popup.active {
            right: 0;
        }

        .sidebar-popup h3 {
            margin-bottom: 20px;
            font-size: 20px;
            color: #333;
        }

        .sidebar-popup .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        .sidebar-popup .option-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .sidebar-popup .option-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .sidebar-popup input[type="checkbox"],
        .sidebar-popup input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* MEDIA MODAL */
        .media-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .media-modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 20px;
            color: #333;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }

        .modal-body {
            padding: 30px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-body .media-preview {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            margin-bottom: 20px;
            border-radius: 8px;
            background: #f5f5f5;
        }

        .modal-body .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .modal-body .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }

        .modal-body .info-value {
            flex: 1;
            color: #333;
        }

        /* LIST VIEW */
        #listView {
            display: none;
        }

        #listView table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        #listView th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #ddd;
        }

        #listView td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #listView tr:hover {
            background: #f9f9f9;
            cursor: pointer;
        }

        #listView .border-orange { border-left: 4px solid #FF8C00; }
        #listView .border-blue { border-left: 4px solid #4A90E2; }
        #listView .border-green { border-left: 4px solid #4CAF50; }
        #listView .border-grey { border-left: 4px solid #9E9E9E; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- HEADER CONTROLS -->
    <div class="header-controls">
        <div class="search-box">
            <input type="text" placeholder="Search..." id="searchInput">
        </div>
        <div class="control-buttons">
            <button onclick="toggleSortPopup()">📊 Sort</button>
            <button>⚙️ Filter</button>
            <button onclick="toggleViewOptions()">👁️ View Options</button>
        </div>
    </div>

    <div class="container">
        <!-- SEARCH RESULTS SECTION -->
        <div id="searchResults">
            <div class="section-title">SEARCH RESULTS</div>
            <div id="resultCounter"></div>
            <div id="searchGrid"></div>
        </div>

        <!-- LIST VIEW -->
        <div id="listView">
            <div class="section-title">ALL MEDIA</div>
            <table id="listTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Storage Type</th>
                        <th>File Path</th>
                        <th>Rating</th>
                        <th>Favorite</th>
                        <th>Notes</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody id="listTableBody"></tbody>
            </table>
        </div>

        <!-- IMAGES SECTION -->
        <div class="section">
            <div class="section-title">IMAGES</div>
            <?php if (count($images) > 0): ?>
                <div class="scroll-container">
                    <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                    <div class="items-wrapper">
                        <?php foreach ($images as $item): ?>
                            <div class="media-item-square" data-id="<?= $item['id'] ?>" onclick="openMediaModal(<?= $item['id'] ?>)">
                                <div class="favorite-badge <?= $item['is_favorite'] ? 'show' : '' ?>">❤️</div>
                                <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                <?php
                                $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $item['file_path']);
                                $isExternal = preg_match('/^https?:\/\//i', $item['file_path']);
                                
                                if ($isImage):
                                    if ($isExternal):
                                ?>
                                    <img src="<?= htmlspecialchars($item['file_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" crossorigin="anonymous">
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($item['file_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                <?php endif; ?>
                                <?php else: ?>
                                    <div class="placeholder"><img src="assets/icons/image-icon.png" alt="Image" style="width: 60px; height: 60px;"></div>
                                <?php endif; ?>
                                <div class="title"><?= htmlspecialchars($item['title']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="scroll-arrow right" onclick="scrollRight(this.parentElement)">›</button>
                </div>
            <?php else: ?>
                <div class="no-items">No images yet. <a href="add_media.php">Add one</a></div>
            <?php endif; ?>
        </div>

        <!-- AUDIO SECTION -->
        <div class="section">
            <div class="section-title">MUSIC</div>
            <?php if (count($audio) > 0): ?>
                <div class="scroll-container">
                    <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                    <div class="items-wrapper">
                        <?php foreach ($audio as $item): ?>
                            <div class="audio-item" onclick="openMediaModal(<?= $item['id'] ?>)">
                                <div class="media-item-circle" data-id="<?= $item['id'] ?>">
                                    <div class="favorite-badge <?= $item['is_favorite'] ? 'show' : '' ?>">❤️</div>
                                    <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                    <img src="assets/icons/audio-icon.png" alt="<?= htmlspecialchars($item['title']) ?>">
                                </div>
                                <div class="title"><?= htmlspecialchars($item['title']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="scroll-arrow right" onclick="scrollRight(this.parentElement)">›</button>
                </div>
            <?php else: ?>
                <div class="no-items">No audio files yet. <a href="add_media.php">Add one</a></div>
            <?php endif; ?>
        </div>

        <!-- VIDEOS SECTION -->
        <div class="section">
            <div class="section-title">VIDEOS</div>
            <?php if (count($videos) > 0): ?>
                <div class="scroll-container">
                    <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                    <div class="items-wrapper">
                        <?php foreach ($videos as $item): ?>
                            <div class="media-item-square" data-id="<?= $item['id'] ?>" onclick="openMediaModal(<?= $item['id'] ?>)">
                                <div class="favorite-badge <?= $item['is_favorite'] ? 'show' : '' ?>">❤️</div>
                                <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                <div class="placeholder"><img src="assets/icons/video-icon.png" alt="Video" style="width: 60px; height: 60px;"></div>
                                <div class="title"><?= htmlspecialchars($item['title']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="scroll-arrow right" onclick="scrollRight(this.parentElement)">›</button>
                </div>
            <?php else: ?>
                <div class="no-items">No videos yet. <a href="add_media.php">Add one</a></div>
            <?php endif; ?>
        </div>

        <!-- DOCUMENTS SECTION -->
        <div class="section">
            <div class="section-title">DOCUMENTS</div>
            <?php if (count($documents) > 0): ?>
                <div class="scroll-container">
                    <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                    <div class="items-wrapper">
                        <?php foreach ($documents as $item): ?>
                            <div class="media-item-portrait" data-id="<?= $item['id'] ?>" onclick="openMediaModal(<?= $item['id'] ?>)">
                                <div class="favorite-badge <?= $item['is_favorite'] ? 'show' : '' ?>">❤️</div>
                                <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                <div class="placeholder"><img src="assets/icons/document-icon.png" alt="Document" style="width: 60px; height: 60px;"></div>
                                <div class="title"><?= htmlspecialchars($item['title']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="scroll-arrow right" onclick="scrollRight(this.parentElement)">›</button>
                </div>
            <?php else: ?>
                <div class="no-items">No documents yet. <a href="add_media.php">Add one</a></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SORT POPUP -->
    <div class="sidebar-popup" id="sortPopup">
        <button class="close-btn" onclick="toggleSortPopup()">×</button>
        <h3>Sort Options</h3>
        <div class="option-group">
            <label><input type="radio" name="sort" value="date-new" checked> Date Added (Newest)</label>
            <label><input type="radio" name="sort" value="date-old"> Date Added (Oldest)</label>
            <label><input type="radio" name="sort" value="title-az"> Title (A-Z)</label>
            <label><input type="radio" name="sort" value="title-za"> Title (Z-A)</label>
            <label><input type="radio" name="sort" value="rating-high"> Rating (High to Low)</label>
            <label><input type="radio" name="sort" value="rating-low"> Rating (Low to High)</label>
        </div>
    </div>

    <!-- VIEW OPTIONS POPUP -->
    <div class="sidebar-popup" id="viewOptionsPopup">
        <button class="close-btn" onclick="toggleViewOptions()">×</button>
        <h3>View Options</h3>
        
        <div class="option-group">
            <h4>Display Options</h4>
            <label><input type="checkbox" id="showRatings"> Show Ratings</label>
            <label><input type="checkbox" id="showFavorites"> Show Favorites</label>
            <label><input type="checkbox" id="mediaTypeColors"> Media Type Colors</label>
        </div>

        <div class="option-group">
            <h4>View Mode</h4>
            <label><input type="radio" name="viewMode" value="grid" checked> Grid View</label>
            <label><input type="radio" name="viewMode" value="list"> List View</label>
        </div>
    </div>

    <!-- MEDIA MODAL -->
    <div class="media-modal" id="mediaModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <button class="modal-close" onclick="closeMediaModal()">×</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <script>
        const allMedia = <?php echo json_encode($allMediaArray); ?>;
        let currentSort = 'date-new';
        let viewOptions = {
            showRatings: false,
            showFavorites: false,
            mediaTypeColors: false,
            viewMode: 'grid'
        };

        function scrollLeft(container) {
            const wrapper = container.querySelector('.items-wrapper');
            wrapper.scrollBy({ left: -300, behavior: 'smooth' });
        }

        function scrollRight(container) {
            const wrapper = container.querySelector('.items-wrapper');
            wrapper.scrollBy({ left: 300, behavior: 'smooth' });
        }

        function toggleSortPopup() {
            document.getElementById('sortPopup').classList.toggle('active');
        }

        function toggleViewOptions() {
            document.getElementById('viewOptionsPopup').classList.toggle('active');
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            const sections = document.querySelectorAll('.section');
            const searchResultsSection = document.getElementById('searchResults');
            const listView = document.getElementById('listView');

            if (query === '') {
                sections.forEach(section => section.style.display = 'block');
                searchResultsSection.style.display = 'none';
                if (viewOptions.viewMode === 'list') {
                    listView.style.display = 'block';
                    sections.forEach(section => section.style.display = 'none');
                }
            } else {
                sections.forEach(section => section.style.display = 'none');
                listView.style.display = 'none';
                
                const results = allMedia.filter(item => 
                    item.title.toLowerCase().includes(query)
                );

                displaySearchResults(results, query);
            }
        });

        function displaySearchResults(results, query) {
            const searchSection = document.getElementById('searchResults');
            const counter = document.getElementById('resultCounter');
            const grid = document.getElementById('searchGrid');

            counter.textContent = `Found ${results.length} result${results.length !== 1 ? 's' : ''} for "${query}"`;

            if (results.length === 0) {
                grid.innerHTML = '<div class="no-items">No results found for "' + escapeHtml(query) + '"</div>';
            } else {
                grid.innerHTML = results.map(item => generateMediaCard(item)).join('');
            }

            searchSection.style.display = 'block';
            applyViewOptions();
        }

        function generateMediaCard(item) {
            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(item.file_path);
            const isExternal = /^https?:\/\//i.test(item.file_path);
            let content = '';
            
            if (item.type === 'image' && isImage) {
                if (isExternal) {
                    content = `<img src="${escapeHtml(item.file_path)}" alt="${escapeHtml(item.title)}" crossorigin="anonymous">`;
                } else {
                    content = `<img src="${escapeHtml(item.file_path)}" alt="${escapeHtml(item.title)}">`;
                }
            } else if (item.type === 'image') {
                content = '<div class="placeholder"><img src="assets/icons/image-icon.png" alt="Image" style="width: 60px; height: 60px;"></div>';
            } else if (item.type === 'video') {
                content = '<div class="placeholder"><img src="assets/icons/video-icon.png" alt="Video" style="width: 60px; height: 60px;"></div>';
            } else if (item.type === 'text') {
                content = '<div class="placeholder"><img src="assets/icons/document-icon.png" alt="Document" style="width: 60px; height: 60px;"></div>';
            } else if (item.type === 'audio') {
                content = '<img src="assets/icons/audio-icon.png" alt="Audio" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">';
            }

            const favoriteClass = item.is_favorite ? 'show' : '';
            const stars = '⭐'.repeat(item.rating);

            return `
                <div class="media-item-square" data-id="${item.id}" data-type="${item.type}" onclick="openMediaModal(${item.id})">
                    <div class="favorite-badge ${favoriteClass}">❤️</div>
                    <div class="rating-badge">${stars}</div>
                    ${content}
                    <div class="title">${escapeHtml(item.title)}</div>
                </div>
            `;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // View Options
        document.getElementById('showRatings').addEventListener('change', function(e) {
            viewOptions.showRatings = e.target.checked;
            applyViewOptions();
        });

        document.getElementById('showFavorites').addEventListener('change', function(e) {
            viewOptions.showFavorites = e.target.checked;
            applyViewOptions();
        });

        document.getElementById('mediaTypeColors').addEventListener('change', function(e) {
            viewOptions.mediaTypeColors = e.target.checked;
            applyViewOptions();
        });

        document.querySelectorAll('input[name="viewMode"]').forEach(radio => {
            radio.addEventListener('change', function(e) {
                viewOptions.viewMode = e.target.value;
                switchViewMode();
            });
        });

        function applyViewOptions() {
            // Show/Hide Ratings
            document.querySelectorAll('.rating-badge').forEach(badge => {
                if (viewOptions.showRatings) {
                    badge.classList.add('show');
                } else {
                    badge.classList.remove('show');
                }
            });

            // Show/Hide Favorites
            if (!viewOptions.showFavorites) {
                document.querySelectorAll('.favorite-badge').forEach(badge => {
                    badge.classList.remove('show');
                });
            } else {
                document.querySelectorAll('.favorite-badge').forEach(badge => {
                    const parent = badge.closest('[data-id]');
                    const id = parseInt(parent.dataset.id);
                    const media = allMedia.find(m => m.id === id);
                    if (media && media.is_favorite) {
                        badge.classList.add('show');
                    }
                });
            }

            // Media Type Colors
            document.querySelectorAll('.media-item-square, .media-item-circle, .media-item-portrait').forEach(item => {
                item.classList.remove('border-orange', 'border-blue', 'border-green', 'border-grey');
                
                if (viewOptions.mediaTypeColors) {
                    const id = parseInt(item.dataset.id);
                    const media = allMedia.find(m => m.id === id);
                    if (media) {
                        if (media.type === 'image') item.classList.add('border-orange');
                        if (media.type === 'video') item.classList.add('border-blue');
                        if (media.type === 'audio') item.classList.add('border-green');
                        if (media.type === 'text') item.classList.add('border-grey');
                    }
                }
            });
        }

        function switchViewMode() {
            const sections = document.querySelectorAll('.section');
            const listView = document.getElementById('listView');
            const searchResults = document.getElementById('searchResults');

            if (viewOptions.viewMode === 'list') {
                sections.forEach(section => section.style.display = 'none');
                searchResults.style.display = 'none';
                listView.style.display = 'block';
                renderListView();
            } else {
                listView.style.display = 'none';
                sections.forEach(section => section.style.display = 'block');
            }
        }

        function renderListView() {
            const tbody = document.getElementById('listTableBody');
            const sortedMedia = sortMedia([...allMedia]);
            
            tbody.innerHTML = sortedMedia.map(item => {
                let borderClass = '';
                if (viewOptions.mediaTypeColors) {
                    if (item.type === 'image') borderClass = 'border-orange';
                    if (item.type === 'video') borderClass = 'border-blue';
                    if (item.type === 'audio') borderClass = 'border-green';
                    if (item.type === 'text') borderClass = 'border-grey';
                }

                const stars = '⭐'.repeat(item.rating);
                const favorite = item.is_favorite ? '❤️' : '';

                return `
                    <tr class="${borderClass}" onclick="openMediaModal(${item.id})">
                        <td>${item.id}</td>
                        <td>${escapeHtml(item.title)}</td>
                        <td>${item.type.toUpperCase()}</td>
                        <td>${item.storage_type}</td>
                        <td title="${escapeHtml(item.file_path)}">${escapeHtml(item.file_path)}</td>
                        <td>${stars}</td>
                        <td>${favorite}</td>
                        <td title="${escapeHtml(item.notes || '')}">${escapeHtml(item.notes || 'N/A')}</td>
                        <td>${item.created_at}</td>
                    </tr>
                `;
            }).join('');
        }

        // Sorting
        document.querySelectorAll('input[name="sort"]').forEach(radio => {
            radio.addEventListener('change', function(e) {
                currentSort = e.target.value;
                applySorting();
            });
        });

        function sortMedia(mediaArray) {
            switch(currentSort) {
                case 'date-new':
                    return mediaArray.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                case 'date-old':
                    return mediaArray.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                case 'title-az':
                    return mediaArray.sort((a, b) => a.title.localeCompare(b.title));
                case 'title-za':
                    return mediaArray.sort((a, b) => b.title.localeCompare(a.title));
                case 'rating-high':
                    return mediaArray.sort((a, b) => b.rating - a.rating);
                case 'rating-low':
                    return mediaArray.sort((a, b) => a.rating - b.rating);
                default:
                    return mediaArray;
            }
        }

        function applySorting() {
            // This would require re-rendering sections with sorted data
            // For now, just re-render list view if active
            if (viewOptions.viewMode === 'list') {
                renderListView();
            }
        }

        // Media Modal
        function openMediaModal(id) {
            const media = allMedia.find(m => m.id === id);
            if (!media) return;

            const modal = document.getElementById('mediaModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');

            modalTitle.textContent = media.title;

            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(media.file_path);
            const isExternal = /^https?:\/\//i.test(media.file_path);
            const stars = '⭐'.repeat(media.rating);
            const favorite = media.is_favorite ? '❤️ Yes' : 'No';

            let preview = '';
            if (media.type === 'image' && isImage) {
                if (isExternal) {
                    preview = `<img src="${escapeHtml(media.file_path)}" class="media-preview" crossorigin="anonymous">`;
                } else {
                    preview = `<img src="${escapeHtml(media.file_path)}" class="media-preview">`;
                }
            } else if (media.type === 'image') {
                preview = `<img src="assets/icons/image-icon.png" class="media-preview" style="max-width: 200px;">`;
            } else if (media.type === 'video') {
                preview = `<img src="assets/icons/video-icon.png" class="media-preview" style="max-width: 200px;">`;
            } else if (media.type === 'audio') {
                preview = `<img src="assets/icons/audio-icon.png" class="media-preview" style="max-width: 200px;">`;
            } else if (media.type === 'text') {
                preview = `<img src="assets/icons/document-icon.png" class="media-preview" style="max-width: 200px;">`;
            }

            modalBody.innerHTML = `
                ${preview}
                <div class="info-row">
                    <div class="info-label">ID:</div>
                    <div class="info-value">${media.id}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Title:</div>
                    <div class="info-value">${escapeHtml(media.title)}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Type:</div>
                    <div class="info-value">${media.type.toUpperCase()}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Storage Type:</div>
                    <div class="info-value">${media.storage_type}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">File Path:</div>
                    <div class="info-value" style="word-break: break-all;">${escapeHtml(media.file_path)}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Rating:</div>
                    <div class="info-value">${stars} (${media.rating}/5)</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Favorite:</div>
                    <div class="info-value">${favorite}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Notes:</div>
                    <div class="info-value">${escapeHtml(media.notes || 'N/A')}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Created Date:</div>
                    <div class="info-value">${media.created_at}</div>
                </div>
            `;

            modal.classList.add('active');
        }

        function closeMediaModal() {
            document.getElementById('mediaModal').classList.remove('active');
        }

        // Close modal on background click
        document.getElementById('mediaModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeMediaModal();
            }
        });

        // Close popups when clicking outside
        document.addEventListener('click', function(e) {
            const sortPopup = document.getElementById('sortPopup');
            const viewPopup = document.getElementById('viewOptionsPopup');
            
            if (!e.target.closest('.sidebar-popup') && !e.target.closest('button')) {
                sortPopup.classList.remove('active');
                viewPopup.classList.remove('active');
            }
        });
    </script>
</body>
</html>