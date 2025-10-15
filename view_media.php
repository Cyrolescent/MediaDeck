<?php
require_once __DIR__ . '/config/dbconfig.php';

$sql = "SELECT id, title, type, storage_type, file_path, rating, is_favorite, notes, created_at FROM media ORDER BY created_at DESC";
$result = $conn->query($sql);

$images = [];
$videos = [];
$audio = [];
$documents = [];
$allMediaArray = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allMediaArray[] = $row;
        switch ($row['type'] = strtolower(trim($row['type']))) {
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
    <link rel="stylesheet" href="assets/css/view.css">
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
            <a href="add_media.php"> <button class="add-media-btn"> Add Media</button> </a>
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
                    <tr id="listTableHeader">
                        <th>ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Storage Type</th>
                        <th>File Path</th>
                        <th class="optional-col rating-col">Rating</th>
                        <th class="optional-col favorite-col">Favorite</th>
                        <th>Notes</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody id="listTableBody"></tbody>
            </table>
        </div>

        <!-- IMAGES SECTION -->
        <div class="section section-grid">
            <div class="section-title">IMAGES</div>
            <?php if (count($images) > 0): ?>
                <div class="scroll-container">
                    <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                    <div class="items-wrapper">
                        <?php foreach ($images as $item): ?>
                            <a href="view_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none;">
                                <div class="media-item-square" data-id="<?= $item['id'] ?>" data-type="image" data-favorite="<?= $item['is_favorite'] ?>" data-rating="<?= $item['rating'] ?>">
                                    <div class="favorite-badge">❤️</div>
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
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <button class="scroll-arrow right" onclick="scrollRight(this.parentElement)">›</button>
                </div>
            <?php else: ?>
                <div class="no-items">No images yet. <a href="add_media.php">Add one</a></div>
            <?php endif; ?>
        </div>

        <!-- AUDIO SECTION -->
        <div class="section section-grid">
            <div class="section-title">MUSIC</div>
            <?php if (count($audio) > 0): ?>
                <div class="scroll-container">
                    <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                    <div class="items-wrapper">
                        <?php foreach ($audio as $item): ?>
                            <a href="view_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none;">
                                <div class="audio-item">
                                    <div class="media-item-circle" data-id="<?= $item['id'] ?>" data-type="audio" data-favorite="<?= $item['is_favorite'] ?>" data-rating="<?= $item['rating'] ?>">
                                        <div class="favorite-badge">❤️</div>
                                        <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                        <img src="assets/icons/audio-icon.png" alt="<?= htmlspecialchars($item['title']) ?>">
                                    </div>
                                    <div class="title"><?= htmlspecialchars($item['title']) ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <button class="scroll-arrow right" onclick="scrollRight(this.parentElement)">›</button>
                </div>
            <?php else: ?>
                <div class="no-items">No audio files yet. <a href="add_media.php">Add one</a></div>
            <?php endif; ?>
        </div>

        <!-- VIDEOS SECTION -->
        <div class="section section-grid">
            <div class="section-title">VIDEOS</div>
            <?php if (count($videos) > 0): ?>
                <div class="scroll-container">
                    <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                    <div class="items-wrapper">
                        <?php foreach ($videos as $item): ?>
                            <a href="view_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none;">
                                <div class="media-item-square" data-id="<?= $item['id'] ?>" data-type="video" data-favorite="<?= $item['is_favorite'] ?>" data-rating="<?= $item['rating'] ?>">
                                    <div class="favorite-badge">❤️</div>
                                    <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                    <div class="placeholder"><img src="assets/icons/video-icon.png" alt="Video" style="width: 60px; height: 60px;"></div>
                                    <div class="title"><?= htmlspecialchars($item['title']) ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <button class="scroll-arrow right" onclick="scrollRight(this.parentElement)">›</button>
                </div>
            <?php else: ?>
                <div class="no-items">No videos yet. <a href="add_media.php">Add one</a></div>
            <?php endif; ?>
        </div>

        <!-- DOCUMENTS SECTION -->
        <div class="section section-grid">
            <div class="section-title">DOCUMENTS</div>
            <?php if (count($documents) > 0): ?>
                <div class="scroll-container">
                    <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                    <div class="items-wrapper">
                        <?php foreach ($documents as $item): ?>
                            <a href="view_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none;">
                                <div class="media-item-portrait" data-id="<?= $item['id'] ?>" data-type="text" data-favorite="<?= $item['is_favorite'] ?>" data-rating="<?= $item['rating'] ?>">
                                    <div class="favorite-badge">❤️</div>
                                    <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                    <div class="placeholder"><img src="assets/icons/document-icon.png" alt="Document" style="width: 60px; height: 60px;"></div>
                                    <div class="title"><?= htmlspecialchars($item['title']) ?></div>
                                </div>
                            </a>
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
        <button class="apply-btn" onclick="applySort()">Apply</button>
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
        
        <button class="apply-btn" onclick="applyViewOptions()">Apply</button>
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
        let isSearching = false;

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
            document.getElementById('viewOptionsPopup').classList.remove('active');
        }

        function toggleViewOptions() {
            document.getElementById('viewOptionsPopup').classList.toggle('active');
            document.getElementById('sortPopup').classList.remove('active');
        }

        // Search functionality with debounce (less sensitive)
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(e.target.value);
            }, 300); // 300ms delay makes it less sensitive
        });

        function performSearch(query) {
            query = query.toLowerCase().trim();
            const gridSections = document.querySelectorAll('.section-grid');
            const searchResultsSection = document.getElementById('searchResults');
            const listView = document.getElementById('listView');

            if (query === '') {
                isSearching = false;
                if (viewOptions.viewMode === 'grid') {
                    gridSections.forEach(section => section.style.display = 'block');
                    searchResultsSection.style.display = 'none';
                    listView.style.display = 'none';
                } else {
                    gridSections.forEach(section => section.style.display = 'none');
                    searchResultsSection.style.display = 'none';
                    listView.style.display = 'block';
                }
                return;
            }

            isSearching = true;
            const results = allMedia.filter(item => 
                item.title.toLowerCase().includes(query)
            );

            if (viewOptions.viewMode === 'grid') {
                gridSections.forEach(section => section.style.display = 'none');
                listView.style.display = 'none';
                displaySearchResults(results, query);
            } else {
                gridSections.forEach(section => section.style.display = 'none');
                searchResultsSection.style.display = 'none';
                listView.style.display = 'block';
                renderListView(results);
            }
        }

        function displaySearchResults(results, query) {
            const searchSection = document.getElementById('searchResults');
            const counter = document.getElementById('resultCounter');
            const grid = document.getElementById('searchGrid');

            counter.textContent = `Found ${results.length} result${results.length !== 1 ? 's' : ''} for "${query}"`;

            if (results.length === 0) {
                grid.innerHTML = '<div class="no-items">No results found for "' + escapeHtml(query) + '"</div>';
            } else {
                grid.innerHTML = results.map(item => generateMediaCard(item)).join('');
                applyDisplayOptions(); // Apply view options to search results
            }

            searchSection.style.display = 'block';
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
                content = '<img src="assets/icons/audio-icon.png" alt="Audio" style="width: 100%; height: 80%; object-fit: cover;">';
            }

            return `
                <a href="view_detail.php?id=${item.id}" style="text-decoration: none;">
                    <div class="media-item-square" data-id="${item.id}" data-type="${item.type}" data-favorite="${item.is_favorite}" data-rating="${item.rating}">
                        <div class="favorite-badge">❤️</div>
                        <div class="rating-badge">${'⭐'.repeat(item.rating)}</div>
                        ${content}
                        <div class="title">${escapeHtml(item.title)}</div>
                    </div>
                </a>
            `;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Apply Sort
        function applySort() {
            const selectedSort = document.querySelector('input[name="sort"]:checked').value;
            currentSort = selectedSort;
            
            if (viewOptions.viewMode === 'list') {
                renderListView(isSearching ? getSearchResults() : allMedia);
            }
            
            toggleSortPopup();
        }

        function getSearchResults() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            return allMedia.filter(item => item.title.toLowerCase().includes(query));
        }

        function sortMedia(mediaArray) {
            const sorted = [...mediaArray];
            switch(currentSort) {
                case 'date-new':
                    return sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                case 'date-old':
                    return sorted.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                case 'title-az':
                    return sorted.sort((a, b) => a.title.localeCompare(b.title));
                case 'title-za':
                    return sorted.sort((a, b) => b.title.localeCompare(a.title));
                case 'rating-high':
                    return sorted.sort((a, b) => b.rating - a.rating);
                case 'rating-low':
                    return sorted.sort((a, b) => a.rating - b.rating);
                default:
                    return sorted;
            }
        }

        // Apply View Options
        function applyViewOptions() {
            // Get selected options
            viewOptions.showRatings = document.getElementById('showRatings').checked;
            viewOptions.showFavorites = document.getElementById('showFavorites').checked;
            viewOptions.mediaTypeColors = document.getElementById('mediaTypeColors').checked;
            viewOptions.viewMode = document.querySelector('input[name="viewMode"]:checked').value;
            
            const gridSections = document.querySelectorAll('.section-grid');
            const searchSection = document.getElementById('searchResults');
            const listView = document.getElementById('listView');

            // Switch view mode
            if (viewOptions.viewMode === 'list') {
                gridSections.forEach(section => section.style.display = 'none');
                searchSection.style.display = 'none';
                listView.style.display = 'block';
                renderListView(isSearching ? getSearchResults() : allMedia);
            } else {
                listView.style.display = 'none';
                if (isSearching) {
                    gridSections.forEach(section => section.style.display = 'none');
                    performSearch(document.getElementById('searchInput').value);
                } else {
                    gridSections.forEach(section => section.style.display = 'block');
                    searchSection.style.display = 'none';
                    applyDisplayOptions();
                }
            }
            
            toggleViewOptions();
        }

        function applyDisplayOptions() {
            // Show/Hide Ratings
            document.querySelectorAll('.rating-badge').forEach(badge => {
                badge.style.display = viewOptions.showRatings ? 'flex' : 'none';
            });

            // Show/Hide Favorites
            document.querySelectorAll('.favorite-badge').forEach(badge => {
                const parent = badge.closest('[data-favorite]');
                if (parent && parent.dataset.favorite === '1' && viewOptions.showFavorites) {
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            });

            // Media Type Colors (only in search mode for grid view)
            if (isSearching && viewOptions.viewMode === 'grid') {
                document.querySelectorAll('#searchGrid [data-type]').forEach(item => {
                    item.classList.remove('border-orange', 'border-blue', 'border-green', 'border-grey');
                    
                    if (viewOptions.mediaTypeColors) {
                        const type = item.dataset.type;
                        if (type === 'image') item.classList.add('border-orange');
                        if (type === 'video') item.classList.add('border-blue');
                        if (type === 'audio') item.classList.add('border-green');
                        if (type === 'text') item.classList.add('border-grey');
                    }
                });
            }
        }

        function renderListView(mediaData = allMedia) {
            const tbody = document.getElementById('listTableBody');
            const sortedMedia = sortMedia(mediaData);
            
            // Show/hide columns based on display options
            const ratingCols = document.querySelectorAll('.rating-col');
            const favoriteCols = document.querySelectorAll('.favorite-col');
            
            ratingCols.forEach(col => {
                col.style.display = viewOptions.showRatings ? 'none' : 'table-cell';
            });
            favoriteCols.forEach(col => {
                col.style.display = viewOptions.showFavorites ? 'none' : 'table-cell';
            });
            
            tbody.innerHTML = sortedMedia.map(item => {
                let borderClass = '';
                if (viewOptions.mediaTypeColors) {
                    if (item.type === 'image') borderClass = 'border-left-orange';
                    if (item.type === 'video') borderClass = 'border-left-blue';
                    if (item.type === 'audio') borderClass = 'border-left-green';
                    if (item.type === 'text') borderClass = 'border-left-grey';
                }

                const stars = '⭐'.repeat(item.rating);
                const favorite = item.is_favorite ? '❤️' : '';

                // File path display
                let filePathDisplay = '';
                if (item.storage_type === 'upload') {
                    filePathDisplay = '<span style="color: #999;">Uploaded in storage</span>';
                } else {
                    filePathDisplay = `<a href="${escapeHtml(item.file_path)}" target="_blank" class="clickable-link" onclick="event.stopPropagation();" title="Click to open, Right-click to copy">${escapeHtml(item.file_path)}</a>`;
                }

                return `
                    <tr class="${borderClass}" onclick="window.location.href='view_detail.php?id=${item.id}'">
                        <td>${item.id}</td>
                        <td>${escapeHtml(item.title)}</td>
                        <td>${item.type.toUpperCase()}</td>
                        <td>${item.storage_type}</td>
                        <td>${filePathDisplay}</td>
                        <td class="rating-col">${stars}</td>
                        <td class="favorite-col">${favorite}</td>
                        <td title="${escapeHtml(item.notes || '')}">${escapeHtml(item.notes || 'N/A')}</td>
                        <td>${item.created_at}</td>
                    </tr>
                `;
            }).join('');
        }

        // Close popups when clicking outside
        document.addEventListener('click', function(e) {
            const sortPopup = document.getElementById('sortPopup');
            const viewPopup = document.getElementById('viewOptionsPopup');
            
            if (!e.target.closest('.sidebar-popup') && !e.target.closest('.control-buttons button')) {
                sortPopup.classList.remove('active');
                viewPopup.classList.remove('active');
            }
        });
    </script>
</body>
</html>