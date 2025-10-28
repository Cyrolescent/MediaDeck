<?php
require_once __DIR__ . '/config/dbconfig.php';
require_once __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/tag_functions.php';

// MEDIA GETTER, dito malaman ng Post kung anong medias available sa database!
$sql = "SELECT id, title, type, storage_type, file_path, thumbnail, rating, is_favorite, notes, created_at FROM media WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$images = [];
$videos = [];
$audio = [];
$documents = [];
$allMediaArray = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mediaTagsData = getMediaTags($pdo, $row['id']);
        $row['tags'] = array_merge($mediaTagsData['default'], $mediaTagsData['custom']);
        $row['tag_ids'] = array_column($row['tags'], 'id');
        
        $allMediaArray[] = $row;
        switch (strtolower(trim($row['type']))) {
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

//Para sa tags lang ito wag pakaelaman
$allDefaultTags = [];
$allCustomTags = [];
foreach (['image', 'video', 'audio', 'text'] as $type) {
    $allDefaultTags = array_merge($allDefaultTags, getDefaultTags($pdo, $type));
    $allCustomTags = array_merge($allCustomTags, getCustomTags($pdo, $current_user_id, $type));
}
$allDefaultTags = array_unique($allDefaultTags, SORT_REGULAR);
$allCustomTags = array_merge($allCustomTags, getCustomTags($pdo, $current_user_id, 'universal'));
$allCustomTags = array_unique($allCustomTags, SORT_REGULAR);
?>


<!-- WEBSITE HTML START CODE HERE HAHAHAHAHAHAHGAHAHDSDUJXYHDGBK -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Media - MediaDeck</title>
    <link rel="stylesheet" href="assets/css/view.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>           <!-- YUNG  MAIN HEADER linker dito siya -->
    <div class="header-controls">                     <!-- YUNG Viewer HEADER search, filter at view options -->
        <div class="search-box">
            <input type="text" placeholder="Search..." id="searchInput">
        </div>
        <div class="control-buttons">           
            <button id="filterBtn" onclick="toggleFilterPopup()">🔍 Filter</button>
            <button id="viewOptionsBtn" onclick="toggleViewOptions()">👁️ View Options</button>
            <button class="add-media-btn" onclick="window.location.href='add_media.php'">Add Media</button>
        </div>
    </div>

    <div class="container">
        <!-- SEARCH RESULTS SECTION -->
        <div id="searchResults" style="display: none;">
            <div class="section-title">SEARCH RESULTS</div>
            <div id="resultCounter"></div>
            <div id="searchGrid"></div>
        </div>

        <!-- LIST VIEW -->
        <div id="listView" style="display: none;">
            <div class="section-title">ALL MEDIA</div>
            <table id="listTable">
                <thead>
                    <tr>
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

        <!-- GRID VIEW SECTIONS -->
        <div id="gridViewContainer">
            <!-- IMAGES SECTION -->
            <div class="section section-grid" data-category="image">
                <div class="section-title">IMAGES</div>
                <?php if (count($images) > 0): ?>
                    <div class="scroll-container">
                        <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                        <div class="items-wrapper">
                            <?php foreach ($images as $item): ?>
                                <a href="view_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none;">
                                    <div class="media-item-square" data-id="<?= $item['id'] ?>" data-type="image" data-favorite="<?= $item['is_favorite'] ?>" data-rating="<?= $item['rating'] ?>" data-storage="<?= $item['storage_type'] ?>" data-tags='<?= json_encode($item['tag_ids']) ?>'>
                                        <div class="favorite-badge">❤️</div>
                                        <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                        <?php
                                        $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $item['file_path']);
                                        $isExternal = preg_match('/^https?:\/\//i', $item['file_path']);
                                        
                                        if (!empty($item['thumbnail'])):
                                        ?>
                                            <img src="<?= htmlspecialchars($item['thumbnail']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                        <?php elseif ($isImage): ?>
                                            <?php if ($item['storage_type'] === 'link'): ?>
                                                <img src="<?= htmlspecialchars($item['file_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" onerror="this.style.display='none'; this.parentElement.innerHTML='📷';>
                                            <?php else: ?>
                                                <img src="<?= htmlspecialchars($item['file_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="placeholder"><img src="assets/icons/image-icon.png" alt="Image" style="width: 120px; height: 120px;"></div>
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
            <div class="section section-grid" data-category="audio">
                <div class="section-title">MUSIC</div>
                <?php if (count($audio) > 0): ?>
                    <div class="scroll-container">
                        <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                        <div class="items-wrapper">
                            <?php foreach ($audio as $item): ?>
                                <a href="view_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none;">
                                    <div class="audio-item">
                                        <div class="media-item-circle" data-id="<?= $item['id'] ?>" data-type="audio" data-favorite="<?= $item['is_favorite'] ?>" data-rating="<?= $item['rating'] ?>" data-storage="<?= $item['storage_type'] ?>" data-tags='<?= json_encode($item['tag_ids']) ?>'>
                                            <div class="favorite-badge">❤️</div>
                                            <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                            <?php if (!empty($item['thumbnail'])): ?>
                                                <img src="<?= htmlspecialchars($item['thumbnail']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                                            <?php else: ?>
                                                <img src="assets/icons/audio-icon.png" alt="<?= htmlspecialchars($item['title']) ?>">
                                            <?php endif; ?>
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
            <div class="section section-grid" data-category="video">
                <div class="section-title">VIDEOS</div>
                <?php if (count($videos) > 0): ?>
                    <div class="scroll-container">
                        <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                        <div class="items-wrapper">
                            <?php foreach ($videos as $item): ?>
                                <a href="view_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none;">
                                    <div class="media-item-square" data-id="<?= $item['id'] ?>" data-type="video" data-favorite="<?= $item['is_favorite'] ?>" data-rating="<?= $item['rating'] ?>" data-storage="<?= $item['storage_type'] ?>" data-tags='<?= json_encode($item['tag_ids']) ?>'>
                                        <div class="favorite-badge">❤️</div>
                                        <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                        <?php if (!empty($item['thumbnail'])): ?>
                                            <img src="<?= htmlspecialchars($item['thumbnail']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="placeholder"><img src="assets/icons/video-icon.png" alt="Video" style="width: 120px; height: 120px;"></div>
                                        <?php endif; ?>
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
            <div class="section section-grid" data-category="text">
                <div class="section-title">DOCUMENTS</div>
                <?php if (count($documents) > 0): ?>
                    <div class="scroll-container">
                        <button class="scroll-arrow left" onclick="scrollLeft(this.parentElement)">‹</button>
                        <div class="items-wrapper">
                            <?php foreach ($documents as $item): ?>
                                <a href="view_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none;">
                                    <div class="media-item-portrait" data-id="<?= $item['id'] ?>" data-type="text" data-favorite="<?= $item['is_favorite'] ?>" data-rating="<?= $item['rating'] ?>" data-storage="<?= $item['storage_type'] ?>" data-tags='<?= json_encode($item['tag_ids']) ?>'>
                                        <div class="favorite-badge">❤️</div>
                                        <div class="rating-badge"><?= str_repeat('⭐', $item['rating']) ?></div>
                                        <div class="placeholder"><img src="assets/icons/document-icon.png" alt="Document" style="width: 120px; height: 120px;"></div>
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
    </div>


    <!-- FILTER POPUP, Dito silter settings menu code -->
    <div class="sidebar-popup" id="filterPopup">
        <button class="close-btn" onclick="toggleFilterPopup()">×</button>
        <h3>Filter Options</h3>
        
        <!-- Ratings check -->
        <div class="filter-section">
            <h4>Ratings ⭐</h4>
            <div class="rating-filter-row">
                <label><input type="checkbox" value="1" class="filter-rating"> 1</label>
                <label><input type="checkbox" value="2" class="filter-rating"> 2</label>
                <label><input type="checkbox" value="3" class="filter-rating"> 3</label>
                <label><input type="checkbox" value="4" class="filter-rating"> 4</label>
                <label><input type="checkbox" value="5" class="filter-rating"> 5</label>
            </div>
        </div>
        
        <!-- Favorites  check-->
        <div class="filter-section">
            <label><input type="checkbox" id="filterFavorite"> Favorites Only</label>
        </div>
        
        <!-- Media Types check if img, vid, doc or aud -->
        <div class="filter-section">
            <h4>Media Types</h4>
            <div class="filter-two-columns">
                <label><input type="checkbox" value="image" class="filter-type"> Images</label>
                <label><input type="checkbox" value="audio" class="filter-type"> Audios</label>
                <label><input type="checkbox" value="video" class="filter-type"> Videos</label>
                <label><input type="checkbox" value="text" class="filter-type"> Documents</label>
            </div>
        </div>
        
        <!-- Storage Method  upload or link ba siya????? -->
        <div class="filter-section">
            <h4>Storage Method</h4>
            <div class="filter-two-columns">
                <label><input type="checkbox" value="upload" class="filter-storage"> Uploaded</label>
                <label><input type="checkbox" value="link" class="filter-storage"> Linked</label>
            </div>
        </div>
        
        <!-- Tag Selection DITO ANG LAKI chicheck if what tags siya-->
        <div class="filter-section">
            <div class="tag-selection-container">
                <!-- Default Tags -->
                <div class="tag-dropdown">
                    <h5>Default Tags</h5>
                    <input type="text" class="tag-search-input-filter" id="searchDefaultTags" placeholder="🔍 Search...">
                    <div class="tag-list-filter" id="defaultTagsList">
                        <?php foreach ($allDefaultTags as $tag): ?>
                            <div class="tag-item-filter">
                                <label>
                                    <input type="checkbox" value="<?= $tag['id'] ?>" class="filter-tag-checkbox" data-tag-name="<?= htmlspecialchars($tag['name']) ?>">
                                    <?= htmlspecialchars($tag['name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Custom Tags -->
                <div class="tag-dropdown">
                    <h5>Custom Tags</h5>
                    <input type="text" class="tag-search-input-filter" id="searchCustomTags" placeholder="🔍 Search...">
                    <div class="tag-list-filter" id="customTagsList">
                        <?php if (!empty($allCustomTags)): ?>
                            <?php foreach ($allCustomTags as $tag): ?>
                                <div class="tag-item-filter">
                                    <label>
                                        <input type="checkbox" value="<?= $tag['id'] ?>" class="filter-tag-checkbox" data-tag-name="<?= htmlspecialchars($tag['name']) ?>">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-custom-tags">No custom tags</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Selected Tags Display -->
        <div class="filter-section">
            <div class="selected-tags-display">
                <div class="selected-tags-header">
                    <h4>Selected Tags</h4>
                    <span class="tag-counter-filter" id="tagCounterFilter">0/10</span>
                </div>
                <div id="selectedTagsDisplay">
                    <div class="no-tags-selected">No tags selected</div>
                </div>
            </div>
        </div>
        
        <button class="apply-btn" onclick="applyFilters()">Apply Filters</button>
        <button class="apply-btn clear-btn" onclick="clearFilters()">Clear All</button>
    </div>



    <!-- VIEW OPTIONS POPUP SETTINGS menu -->
    <div class="sidebar-popup" id="viewOptionsPopup">
        <button class="close-btn" onclick="toggleViewOptions()">×</button>
        <h3>View Options</h3>
        
        <!-- View Mode Selection -->
        <div class="option-group">
            <h4>View Mode</h4>
            <label><input type="radio" name="viewMode" value="grid" checked> Grid View</label>
            <label><input type="radio" name="viewMode" value="list"> List View</label>
        </div>
        
        <!-- Display Options for Grid View -->
        <div class="option-group" id="gridDisplayOptions">
            <h4>Display Options</h4>
            <label><input type="checkbox" id="showRatings"> Show Ratings</label>
            <label><input type="checkbox" id="showFavorites"> Show Favorites</label>
        </div>
        
        <!-- Display Options for List View -->
        <div class="option-group" id="listDisplayOptions" style="display: none;">
            <h4>Display Options</h4>
            <label><input type="checkbox" id="mediaTypeColors"> Media Type Colors</label>
        </div>
        
        <!-- Sort Options -->
        <div class="option-group">
            <h4>Sort By</h4>
            <label><input type="radio" name="sortOption" value="date-new" checked> Date Added (Newest)</label>
            <label><input type="radio" name="sortOption" value="date-old"> Date Added (Oldest)</label>
            <label><input type="radio" name="sortOption" value="title-az"> Title (A-Z)</label>
            <label><input type="radio" name="sortOption" value="title-za"> Title (Z-A)</label>
            <label><input type="radio" name="sortOption" value="rating-high"> Rating (High to Low)</label>
            <label><input type="radio" name="sortOption" value="rating-low"> Rating (Low to High)</label>
        </div>
        
        <button class="apply-btn" onclick="applyViewOptions()">Apply</button>
    </div>
    <script>
        const allMedia = <?php echo json_encode($allMediaArray); ?>;
        const allTagsForFilter = <?php echo json_encode(array_merge($allDefaultTags, $allCustomTags)); ?>;
        
        let currentSort = 'date-new';
        let viewOptions = {
            showRatings: false,
            showFavorites: false,
            mediaTypeColors: false,
            viewMode: 'grid'
        };
        let activeFilters = {
            ratings: [],
            types: [],
            favorite: false,
            storage: [],
            tags: []
        };
        let selectedFilterTags = [];
        const MAX_FILTER_TAGS = 10;
        let isSearching = false;

        function loadSettings() {
            const saved = localStorage.getItem('mediaDeckSettings');
            if (saved) {
                try {
                    const settings = JSON.parse(saved);
                    currentSort = settings.sort || 'date-new';
                    viewOptions = settings.viewOptions || viewOptions;
                    activeFilters = settings.filters || activeFilters;
                    selectedFilterTags = settings.selectedFilterTags || [];
                    
                    document.querySelector(`input[name="sortOption"][value="${currentSort}"]`).checked = true;
                    document.querySelector(`input[name="viewMode"][value="${viewOptions.viewMode}"]`).checked = true;
                    document.getElementById('showRatings').checked = viewOptions.showRatings;
                    document.getElementById('showFavorites').checked = viewOptions.showFavorites;
                    document.getElementById('mediaTypeColors').checked = viewOptions.mediaTypeColors;
                    
                    activeFilters.ratings.forEach(rating => {
                        const cb = document.querySelector(`.filter-rating[value="${rating}"]`);
                        if (cb) cb.checked = true;
                    });
                    activeFilters.types.forEach(type => {
                        const cb = document.querySelector(`.filter-type[value="${type}"]`);
                        if (cb) cb.checked = true;
                    });
                    activeFilters.storage.forEach(storage => {
                        const cb = document.querySelector(`.filter-storage[value="${storage}"]`);
                        if (cb) cb.checked = true;
                    });
                    document.getElementById('filterFavorite').checked = activeFilters.favorite;
                    
                    selectedFilterTags.forEach(tagId => {
                        const cb = document.querySelector(`.filter-tag-checkbox[value="${tagId}"]`);
                        if (cb) cb.checked = true;
                    });
                    updateSelectedTagsDisplay();
                    updateButtonTints();
                    
                } catch (e) {
                    console.error('Error loading settings:', e);
                }
            }
        }

        function saveSettings() {
            const settings = {
                sort: currentSort,
                viewOptions: viewOptions,
                filters: activeFilters,
                selectedFilterTags: selectedFilterTags
            };
            localStorage.setItem('mediaDeckSettings', JSON.stringify(settings));
        }

        function updateButtonTints() {
            const filterBtn = document.getElementById('filterBtn');
            const viewOptionsBtn = document.getElementById('viewOptionsBtn');
            
            const hasActiveFilters = activeFilters.ratings.length > 0 ||
                                    activeFilters.types.length > 0 ||
                                    activeFilters.favorite ||
                                    activeFilters.storage.length > 0 ||
                                    activeFilters.tags.length > 0;
            
            if (hasActiveFilters) {
                filterBtn.classList.add('active-btn');
            } else {
                filterBtn.classList.remove('active-btn');
            }
            
            const hasActiveViewOptions = viewOptions.showRatings ||
                                         viewOptions.showFavorites ||
                                         viewOptions.mediaTypeColors ||
                                         currentSort !== 'date-new' ||
                                         viewOptions.viewMode !== 'grid';
            
            if (hasActiveViewOptions) {
                viewOptionsBtn.classList.add('active-btn');
            } else {
                viewOptionsBtn.classList.remove('active-btn');
            }
        }

        // PAG GUSTO MAG SCROLL DITO ANG CODE
        function scrollLeft(container) {
            const wrapper = container.querySelector('.items-wrapper');
            wrapper.scrollBy({ left: -300, behavior: 'smooth' });
        }

        function scrollRight(container) {
            const wrapper = container.querySelector('.items-wrapper');
            wrapper.scrollBy({ left: 300, behavior: 'smooth' });
        }

        // Toggle popups
        function toggleFilterPopup() {
            document.getElementById('filterPopup').classList.toggle('active');
            document.getElementById('viewOptionsPopup').classList.remove('active');
        }

        function toggleViewOptions() {
            document.getElementById('viewOptionsPopup').classList.toggle('active');
            document.getElementById('filterPopup').classList.remove('active');
            updateViewOptionsDisplay();
        }

        // Updater ng menu depends sa view options
        function updateViewOptionsDisplay() {
            const viewMode = document.querySelector('input[name="viewMode"]:checked').value;
            document.getElementById('gridDisplayOptions').style.display = viewMode === 'grid' ? 'block' : 'none';
            document.getElementById('listDisplayOptions').style.display = viewMode === 'list' ? 'block' : 'none';
        }

        document.querySelectorAll('input[name="viewMode"]').forEach(radio => {
            radio.addEventListener('change', updateViewOptionsDisplay);
        });

        document.getElementById('searchDefaultTags').addEventListener('input', function(e) {
            filterTagList('defaultTagsList', e.target.value);
        });

        document.getElementById('searchCustomTags').addEventListener('input', function(e) {
            filterTagList('customTagsList', e.target.value);
        });

        function filterTagList(listId, query) {
            const list = document.getElementById(listId);
            const items = list.querySelectorAll('.tag-item-filter');
            query = query.toLowerCase();
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(query) ? 'block' : 'none';
            });
        }

        // Selector ng tags po
        document.querySelectorAll('.filter-tag-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const tagId = parseInt(this.value);
                const tagName = this.dataset.tagName;
                
                if (this.checked) {
                    if (selectedFilterTags.length < MAX_FILTER_TAGS) {
                        selectedFilterTags.push(tagId);
                    } else {
                        alert('Maximum 10 tags allowed!');
                        this.checked = false;
                        return;
                    }
                } else {
                    const index = selectedFilterTags.indexOf(tagId);
                    if (index > -1) {
                        selectedFilterTags.splice(index, 1);
                    }
                }
                
                updateSelectedTagsDisplay();
            });
        });

        // ishowshow kung anong selected tags
        function updateSelectedTagsDisplay() {
            const display = document.getElementById('selectedTagsDisplay');
            const counter = document.getElementById('tagCounterFilter');
            
            counter.textContent = `${selectedFilterTags.length}/10`;
            counter.classList.toggle('limit-reached', selectedFilterTags.length >= MAX_FILTER_TAGS);
            
            if (selectedFilterTags.length === 0) {
                display.innerHTML = '<div class="no-tags-selected">No tags selected</div>';
                return;
            }
            
            display.innerHTML = selectedFilterTags.map(tagId => {
                const tag = allTagsForFilter.find(t => t.id == tagId);
                if (tag) {
                    return `<span class="selected-tag-badge" onclick="removeFilterTag(${tagId})">${escapeHtml(tag.name)}</span>`;
                }
                return '';
            }).join('');
        }

        function removeFilterTag(tagId) {
            const index = selectedFilterTags.indexOf(tagId);
            if (index > -1) {
                selectedFilterTags.splice(index, 1);
            }
            
            const checkbox = document.querySelector(`.filter-tag-checkbox[value="${tagId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
            
            updateSelectedTagsDisplay();
        }

        // Searcher FUNCTION CODE for search box
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(e.target.value);
            }, 300);
        });

        function performSearch(query) {
            query = query.toLowerCase().trim();
            isSearching = query !== '';
            
            if (!isSearching && !hasActiveFilters()) {
                refreshView();
                return;
            }

            const filtered = filterMedia(allMedia);
            const results = isSearching ? filtered.filter(item => 
                item.title.toLowerCase().includes(query)
            ) : filtered;

            if (viewOptions.viewMode === 'grid') {
                document.getElementById('gridViewContainer').style.display = 'none';
                document.getElementById('listView').style.display = 'none';
                displaySearchResults(results, query);
            } else {
                document.getElementById('gridViewContainer').style.display = 'none';
                document.getElementById('searchResults').style.display = 'none';
                document.getElementById('listView').style.display = 'block';
                renderListView(results);
            }
        }

        function hasActiveFilters() {
            return activeFilters.ratings.length > 0 ||
                   activeFilters.types.length > 0 ||
                   activeFilters.favorite ||
                   activeFilters.storage.length > 0 ||
                   activeFilters.tags.length > 0;
        }

        function displaySearchResults(results, query) {
            const searchSection = document.getElementById('searchResults');
            const counter = document.getElementById('resultCounter');
            const grid = document.getElementById('searchGrid');

            if (isSearching) {
                counter.textContent = `Found ${results.length} result${results.length !== 1 ? 's' : ''} for "${query}"`;
            } else {
                counter.textContent = `Filtered Results: ${results.length} item${results.length !== 1 ? 's' : ''}`;
            }

            if (results.length === 0) {
                grid.innerHTML = '<div class="no-items">No results found</div>';
            } else {
                const sorted = sortMedia(results);
                grid.innerHTML = sorted.map(item => generateMediaCard(item)).join('');
                applyDisplayOptions();
            }

            searchSection.style.display = 'block';
        }

        function generateMediaCard(item) {
            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(item.file_path);
            const isExternal = /^https?:\/\//i.test(item.file_path);
            let content = '';
            
            if (item.thumbnail) {
                content = `<img src="${escapeHtml(item.thumbnail)}" alt="${escapeHtml(item.title)}">`;
            } else if (item.type === 'image' && isImage) {
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

        // Filterer po rito 
        function applyFilters() {
            activeFilters.ratings = Array.from(document.querySelectorAll('.filter-rating:checked')).map(cb => parseInt(cb.value));
            activeFilters.types = Array.from(document.querySelectorAll('.filter-type:checked')).map(cb => cb.value);
            activeFilters.favorite = document.getElementById('filterFavorite').checked;
            activeFilters.storage = Array.from(document.querySelectorAll('.filter-storage:checked')).map(cb => cb.value);
            activeFilters.tags = selectedFilterTags;
            
            saveSettings();
            updateButtonTints();
            toggleFilterPopup();
            refreshView();
        }

        function clearFilters() {
            document.querySelectorAll('.filter-rating').forEach(cb => cb.checked = false);
            document.querySelectorAll('.filter-type').forEach(cb => cb.checked = false);
            document.getElementById('filterFavorite').checked = false;
            document.querySelectorAll('.filter-storage').forEach(cb => cb.checked = false);
            document.querySelectorAll('.filter-tag-checkbox').forEach(cb => cb.checked = false);
            
            selectedFilterTags = [];
            updateSelectedTagsDisplay();
            
            activeFilters = {
                ratings: [],
                types: [],
                favorite: false,
                storage: [],
                tags: []
            };
            
            saveSettings();
            updateButtonTints();
            toggleFilterPopup();
            refreshView();
        }

        function filterMedia(mediaArray) {
            return mediaArray.filter(item => {
                // Rating filter
                if (activeFilters.ratings.length > 0 && !activeFilters.ratings.includes(item.rating)) {
                    return false;
                }
                
                // Type filter
                if (activeFilters.types.length > 0 && !activeFilters.types.includes(item.type)) {
                    return false;
                }
                
                // Favorite filter
                if (activeFilters.favorite && item.is_favorite != 1) {
                    return false;
                }
                
                // Storage filter
                if (activeFilters.storage.length > 0 && !activeFilters.storage.includes(item.storage_type)) {
                    return false;
                }
                
                // Tag filter (must have ALL selected tags)
                if (activeFilters.tags.length > 0) {
                    const hasAllTags = activeFilters.tags.every(tagId => 
                        item.tag_ids && item.tag_ids.includes(tagId)
                    );
                    if (!hasAllTags) {
                        return false;
                    }
                }
                
                return true;
            });
        }

        // Sort functionality
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
            viewOptions.showRatings = document.getElementById('showRatings').checked;
            viewOptions.showFavorites = document.getElementById('showFavorites').checked;
            viewOptions.mediaTypeColors = document.getElementById('mediaTypeColors').checked;
            viewOptions.viewMode = document.querySelector('input[name="viewMode"]:checked').value;
            currentSort = document.querySelector('input[name="sortOption"]:checked').value;
            
            saveSettings();
            updateButtonTints();
            toggleViewOptions();
            refreshView();
        }

        function refreshView() {
            const searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
            
            // If searching or filtering, show search/list view
            if (searchQuery || hasActiveFilters()) {
                performSearch(searchQuery);
                return;
            }

            // No search, no filters - show normal grid or list view
            if (viewOptions.viewMode === 'list') {
                document.getElementById('gridViewContainer').style.display = 'none';
                document.getElementById('searchResults').style.display = 'none';
                document.getElementById('listView').style.display = 'block';
                renderListView(allMedia);
            } else {
                document.getElementById('listView').style.display = 'none';
                document.getElementById('searchResults').style.display = 'none';
                document.getElementById('gridViewContainer').style.display = 'block';
                renderGridView(allMedia);
            }
        }

        function renderGridView(mediaData) {
            const container = document.getElementById('gridViewContainer');
            const sections = container.querySelectorAll('.section-grid');
            
            sections.forEach(section => {
                const category = section.dataset.category;
                const categoryMedia = mediaData.filter(item => item.type === category);
                const sorted = sortMedia(categoryMedia);
                
                const wrapper = section.querySelector('.items-wrapper');
                if (wrapper && sorted.length > 0) {
                    wrapper.innerHTML = sorted.map(item => generateCategoryCard(item, category)).join('');
                }
            });
            
            applyDisplayOptions();
        }

        function generateCategoryCard(item, category) {
            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(item.file_path);
            const isExternal = /^https?:\/\//i.test(item.file_path);
            
            let cardClass = 'media-item-square';
            if (category === 'audio') cardClass = 'media-item-circle';
            if (category === 'text') cardClass = 'media-item-portrait';
            
            let content = '';
            if (item.thumbnail) {
                content = `<img src="${escapeHtml(item.thumbnail)}" alt="${escapeHtml(item.title)}" style="width: 100%; height: 100%; object-fit: cover;">`;
            } else if (category === 'image' && isImage) {
                if (isExternal) {
                    content = `<img src="${escapeHtml(item.file_path)}" alt="${escapeHtml(item.title)}" crossorigin="anonymous">`;
                } else {
                    content = `<img src="${escapeHtml(item.file_path)}" alt="${escapeHtml(item.title)}">`;
                }
            } else if (category === 'image') {
                content = '<div class="placeholder"><img src="assets/icons/image-icon.png" alt="Image" style="width: 120px; height: 120px;"></div>';
            } else if (category === 'video') {
                content = '<div class="placeholder"><img src="assets/icons/video-icon.png" alt="Video" style="width: 120px; height: 120px;"></div>';
            } else if (category === 'text') {
                content = '<div class="placeholder"><img src="assets/icons/document-icon.png" alt="Document" style="width: 120px; height: 120px;"></div>';
            } else if (category === 'audio') {
                content = '<img src="assets/icons/audio-icon.png" alt="Audio">';
            }
            
            if (category === 'audio') {
                return `
                    <a href="view_detail.php?id=${item.id}" style="text-decoration: none;">
                        <div class="audio-item">
                            <div class="${cardClass}" data-id="${item.id}" data-type="${category}" data-favorite="${item.is_favorite}" data-rating="${item.rating}">
                                <div class="favorite-badge">❤️</div>
                                <div class="rating-badge">${'⭐'.repeat(item.rating)}</div>
                                ${content}
                            </div>
                            <div class="title">${escapeHtml(item.title)}</div>
                        </div>
                    </a>
                `;
            }
            
            return `
                <a href="view_detail.php?id=${item.id}" style="text-decoration: none;">
                    <div class="${cardClass}" data-id="${item.id}" data-type="${category}" data-favorite="${item.is_favorite}" data-rating="${item.rating}">
                        <div class="favorite-badge">❤️</div>
                        <div class="rating-badge">${'⭐'.repeat(item.rating)}</div>
                        ${content}
                        <div class="title">${escapeHtml(item.title)}</div>
                    </div>
                </a>
            `;
        }

        function applyDisplayOptions() {
            // Show/Hide Ratings SHOW OPTIONNNNNNNN Ratings 1-5
            document.querySelectorAll('.rating-badge').forEach(badge => {
                badge.style.display = viewOptions.showRatings ? 'flex' : 'none';
            });

            // Show/Hide Favorites Heart
            document.querySelectorAll('.favorite-badge').forEach(badge => {
                const parent = badge.closest('[data-favorite]');
                if (parent && parent.dataset.favorite == '1' && viewOptions.showFavorites) {
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            });
        }

        function renderListView(mediaData = allMedia) {
            const tbody = document.getElementById('listTableBody');
            const sorted = sortMedia(mediaData);
            
            tbody.innerHTML = sorted.map(item => {
                let rowClass = '';
                if (viewOptions.mediaTypeColors) {
                    if (item.type === 'image') rowClass = 'row-image';
                    if (item.type === 'video') rowClass = 'row-video';
                    if (item.type === 'audio') rowClass = 'row-audio';
                    if (item.type === 'text') rowClass = 'row-text';
                }

                const stars = '⭐'.repeat(item.rating);
                const favorite = item.is_favorite == 1 ? '❤️' : '';

                // File path display
                let filePathDisplay = '';
                if (item.storage_type === 'upload') {
                    filePathDisplay = '<span style="color: #999;">Uploaded in storage</span>';
                } else {
                    filePathDisplay = `<a href="${escapeHtml(item.file_path)}" target="_blank" class="clickable-link" onclick="event.stopPropagation();" title="Click to open">${escapeHtml(item.file_path)}</a>`;
                }

                return `
                    <tr class="${rowClass}" onclick="window.location.href='view_detail.php?id=${item.id}'" style="cursor: pointer;">
                        <td>${escapeHtml(item.title)}</td>
                        <td>${item.type.toUpperCase()}</td>
                        <td>${item.storage_type}</td>
                        <td>${filePathDisplay}</td>
                        <td>${stars}</td>
                        <td>${favorite}</td>
                        <td title="${escapeHtml(item.notes || '')}">${escapeHtml(item.notes || 'N/A')}</td>
                        <td>${item.created_at}</td>
                    </tr>
                `;
            }).join('');
        }

        // Close popups when clicking outside
        document.addEventListener('click', function(e) {
            const filterPopup = document.getElementById('filterPopup');
            const viewPopup = document.getElementById('viewOptionsPopup');
            
            if (!e.target.closest('.sidebar-popup') && !e.target.closest('.control-buttons button')) {
                filterPopup.classList.remove('active');
                viewPopup.classList.remove('active');
            }
        });

        // Initialize on page load
        loadSettings();
        updateViewOptionsDisplay();
        refreshView();
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>