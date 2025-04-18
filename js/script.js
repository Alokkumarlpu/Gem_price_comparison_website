/**
 * Main JavaScript file for GeM Price Comparison Tool (v1.3)
 * Handles: Watchlist, Initial Load, Price Fetching, Mobile Menu.
 * Corrected event listener target for global button handling.
 */

document.addEventListener('DOMContentLoaded', function() {

    // --- Global Elements & State ---
    const bodyElement = document.body;
    const IS_LOGGED_IN = bodyElement.dataset.loggedIn === 'true';
    const mainContent = document.getElementById('main-content'); // << Use this for main event delegation

    // --- Product Listing Specific Elements (used within functions) ---
    const productListingContainer = document.getElementById('product-listing-container');
    const initialLoader = document.getElementById('initial-loader');
    const listingHeading = document.getElementById('listing-heading');

    // --- Mobile Menu Elements ---
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuClose = document.getElementById('mobile-menu-close');

    /** Mobile Menu Functionality */
    if (mobileMenuToggle && mobileMenu && mobileMenuClose) {
        const openMenu=()=>{mobileMenu.hidden=!1;setTimeout(()=>{mobileMenu.setAttribute("aria-modal","true");mobileMenuToggle.setAttribute("aria-expanded","true");document.body.style.overflow="hidden";if(mainContent)mainContent.setAttribute("aria-hidden","true");mobileMenuClose.focus()},10)};const closeMenu=()=>{mobileMenu.removeAttribute("aria-modal");mobileMenuToggle.setAttribute("aria-expanded","false");mobileMenu.addEventListener("transitionend",function handleTransitionEnd(){mobileMenu.hidden=!0;mobileMenu.removeEventListener("transitionend",handleTransitionEnd)},{once:!0});document.body.style.overflow="";if(mainContent)mainContent.removeAttribute("aria-hidden");mobileMenuToggle.focus()};mobileMenuToggle.addEventListener("click",()=>{const e=mobileMenuToggle.getAttribute("aria-expanded")==="true";if(!e)openMenu();else closeMenu()});mobileMenuClose.addEventListener("click",closeMenu);document.addEventListener("keydown",e=>{if(e.key==="Escape"&&mobileMenuToggle.getAttribute("aria-expanded")==="true")closeMenu()});mobileMenu.addEventListener("click",e=>{if(e.target===mobileMenu)closeMenu()});
    }

    /** Load Initial Products */
    function loadInitialProducts() {
       // Only run if the container exists on the current page (index.php)
       if (!productListingContainer || !initialLoader) return;
       initialLoader.style.display = 'block';
       fetch('php/get_products.php')
           .then(response => handleFetchResponse(response, "initial products"))
           .then(data => {
               initialLoader.style.display = 'none';
               if (data.error) { displayError(data.error, productListingContainer); }
               else if (data.results && data.results.length > 0) { displayInitialCards(data.results, productListingContainer); if(listingHeading) listingHeading.textContent = "Product Comparisons"; }
               else { displayError("No products found.", productListingContainer); }
           })
           .catch(error => { console.error('Initial Product Load Error:', error); initialLoader.style.display = 'none'; displayError(`Failed to load products. (${error.message})`, productListingContainer); });
    }

    /** Display Initial Cards */
    function displayInitialCards(products, container) {
       container.innerHTML = '';
       products.forEach(product => {
           const productCard = document.createElement('div');
           productCard.dataset.productId = product.id;
           productCard.classList.add('product-card', 'initial-state');
           const productName = product.name || 'Unnamed Product';
           const productDescription = product.description || 'No description.';
           const imageUrl = product.image_url || 'images/placeholder.png';
           productCard.innerHTML = `
               <div class="product-image"><img src="${escapeHtml(imageUrl)}" alt="Image of ${escapeHtml(productName)}" loading="lazy"></div>
               <div class="product-details"><h3>${escapeHtml(productName)}</h3><p class="product-description">${escapeHtml(productDescription)}</p><button class="button button-secondary compare-button">Compare Prices</button><div class="price-comparison-area loading-state"><div class="loader"></div></div><div class="price-comparison-area results-state"></div></div>`;
           container.appendChild(productCard);
       });
    }

    /**
     * --------------------------------------
     * Main Click Event Listener (Delegation on #main-content)
     * --------------------------------------
     */
    if (mainContent) {
        mainContent.addEventListener('click', function(event) {

            // --- Handle "Compare Prices" button click ---
            const compareButton = event.target.closest('.compare-button');
            if (compareButton) {
                const productCard = compareButton.closest('.product-card');
                if (productCard && productCard.dataset.productId) {
                    const productId = productCard.dataset.productId;
                    // Prevent multiple clicks
                    if (productCard.classList.contains('loading') || productCard.classList.contains('loaded')) {
                        return;
                    }
                    fetchAndDisplayPrices(productId, productCard);
                }
            }

            // --- Handle Watchlist button click ---
            const watchlistButton = event.target.closest('.watchlist-btn');
            if (watchlistButton) {
                 handleWatchlistButtonClick(watchlistButton);
            }
        });
    } else {
        console.error("Main content container (#main-content) not found. Event delegation will not work.");
    }

     /** Fetch and Display Prices */
    function fetchAndDisplayPrices(productId, productCard) {
       const priceResultsArea = productCard.querySelector('.price-comparison-area.results-state');
       const priceLoadingArea = productCard.querySelector('.price-comparison-area.loading-state');
       const compareButton = productCard.querySelector('.compare-button');
       if (!priceResultsArea || !priceLoadingArea) { console.error("Price display areas not found."); return; }
       productCard.classList.remove('initial-state', 'loaded'); productCard.classList.add('loading'); if (compareButton) compareButton.style.display = 'none'; priceLoadingArea.style.display = 'block'; priceResultsArea.innerHTML = '';
       fetch(`php/get_product_prices.php?id=${productId}`)
           .then(response => handleFetchResponse(response, `prices for product ID ${productId}`))
           .then(data => {
               productCard.classList.remove('loading'); priceLoadingArea.style.display = 'none';
               if (data.error) { priceResultsArea.innerHTML = `<p class="error-text">Error: ${escapeHtml(data.error)}</p>`; }
               else if (data.prices && data.prices.length > 0) { renderPriceComparison(productId, data.prices, priceResultsArea); productCard.classList.add('loaded'); }
               else { priceResultsArea.innerHTML = `<p class="placeholder-text">No price data found.</p>`; productCard.classList.add('loaded'); }
               priceResultsArea.style.display = 'block';
           })
           .catch(error => { console.error('Fetch Prices Error:', error); productCard.classList.remove('loading'); priceLoadingArea.style.display = 'none'; priceResultsArea.style.display = 'block'; priceResultsArea.innerHTML = `<p class="error-text">Failed to load prices. ${escapeHtml(error.message)}</p>`; productCard.classList.add('loaded'); });
    }

    /** Render Price Comparison including Watchlist buttons */
    function renderPriceComparison(productId, prices, targetElement) {
        targetElement.innerHTML = '';
        let lowestPrice = Infinity;
        prices.forEach(p => { if (p.price !== null && p.is_available) lowestPrice = Math.min(lowestPrice, p.price); });
        const comparisonDiv = document.createElement('div');
        comparisonDiv.classList.add('price-comparison');

        prices.forEach(priceInfo => {
            const priceSourceDiv = document.createElement('div');
            const isLowest = priceInfo.price !== null && priceInfo.is_available && priceInfo.price === lowestPrice;
            const sourceLower = priceInfo.source.toLowerCase();
            priceSourceDiv.classList.add('price-source', `${escapeHtml(sourceLower)}-price`);
            if (isLowest) priceSourceDiv.classList.add('lower-price');
            if (!priceInfo.is_available) priceSourceDiv.classList.add('unavailable');
            const priceValueHtml = (priceInfo.price !== null) ? `<span class="price-value">₹${priceInfo.price.toFixed(2)}</span>` : `<span class="price-unavailable">N/A</span>`;
            const availabilityHtml = !priceInfo.is_available ? `<span class="availability-info">(Unavailable)</span>` : '';
            const linkHtml = priceInfo.url ? `<a href="${escapeHtml(priceInfo.url)}" target="_blank" rel="noopener noreferrer nofollow" class="visit-link">Visit ${escapeHtml(priceInfo.source)}</a>` : '';

            let watchlistButtonHtml = '';
            if (IS_LOGGED_IN && (priceInfo.price !== null || priceInfo.url)) {
                 // TODO: Fetch actual watchlist status for this item from backend for initial render
                 const isCurrentlyInWatchlist = false; // << Placeholder: Assumes NOT in watchlist initially
                 const currentAction = isCurrentlyInWatchlist ? 'remove' : 'add';
                 const buttonText = isCurrentlyInWatchlist ? '- Remove' : '+ Watchlist';
                 const buttonClass = isCurrentlyInWatchlist ? 'remove in-watchlist' : 'add';
                 const ariaLabelAction = isCurrentlyInWatchlist ? 'Remove' : 'Add';

                 watchlistButtonHtml = `
                     <button class="button button-small watchlist-btn ${buttonClass}"
                             data-product-id="${productId}"
                             data-source="${escapeHtml(priceInfo.source)}"
                             data-action="${currentAction}"
                             aria-label="${ariaLabelAction} ${escapeHtml(priceInfo.source)} listing for product to watchlist">
                         ${buttonText}
                     </button>`;
            }
            priceSourceDiv.innerHTML = `<span class="platform-label">${escapeHtml(priceInfo.source)} ${availabilityHtml}</span> ${priceValueHtml} <div class="price-actions">${linkHtml}${watchlistButtonHtml}</div>`;
            comparisonDiv.appendChild(priceSourceDiv);
        });
        targetElement.appendChild(comparisonDiv);
        const validAvailablePrices = prices.filter(p => p.price !== null && p.is_available); if (validAvailablePrices.length === 2) { const diff = Math.abs(validAvailablePrices[0].price - validAvailablePrices[1].price); const diffDiv = document.createElement('div'); diffDiv.classList.add('price-difference'); diffDiv.textContent = `Difference: ₹${diff.toFixed(2)}`; targetElement.appendChild(diffDiv); }
    }

    /** Handle Watchlist Button Click */
    function handleWatchlistButtonClick(button) {
        if (!IS_LOGGED_IN) { console.warn('User not logged in.'); return; }
        const productId = button.dataset.productId;
        const source = button.dataset.source;
        const currentAction = button.dataset.action; // Action to perform

        if (!productId || !source || !currentAction) { console.error("Missing data attributes on watchlist button:", button); return; }

        button.disabled = true; button.textContent = '...';

        fetch('php/watchlist_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `product_id=${encodeURIComponent(productId)}&source=${encodeURIComponent(source)}&action=${encodeURIComponent(currentAction)}`
        })
        .then(response => handleFetchResponse(response, "watchlist update"))
        .then(data => {
            if (data.success) {
                // Update button based on the action reported as successfully completed by backend
                if (data.action === 'added') { // Item was ADDED
                    button.textContent = `- Remove`;
                    button.dataset.action = 'remove'; // Next click will remove
                    button.classList.remove('add');
                    button.classList.add('remove', 'in-watchlist');
                    button.setAttribute('aria-label', `Remove ${escapeHtml(source)} listing from watchlist`);
                } else if (data.action === 'removed') { // Item was REMOVED
                    button.textContent = `+ Watchlist`;
                    button.dataset.action = 'add'; // Next click will add
                    button.classList.remove('remove', 'in-watchlist');
                    button.classList.add('add');
                    button.setAttribute('aria-label', `Add ${escapeHtml(source)} listing to watchlist`);

                    // Fade out on watchlist page if removal successful
                    if (window.location.pathname.includes('watchlist.php')) {
                        const cardItem = button.closest('.watchlist-item');
                         if (cardItem) {
                             cardItem.classList.add('removing'); // Add class to trigger CSS transition
                             setTimeout(() => cardItem.remove(), 400); // Remove after transition
                         }
                    }
                }
            } else {
                 console.error(`Watchlist Error: ${data.error || 'Could not update watchlist.'}`);
                 // Reset button text based on the action it was TRYING to perform
                 button.textContent = (currentAction === 'add') ? '+ Watchlist' : '- Remove';
            }
        })
        .catch(error => {
             console.error('Watchlist Fetch/Parse Error:', error);
             // Reset button text based on the action it was TRYING to perform
             button.textContent = (currentAction === 'add') ? '+ Watchlist' : '- Remove';
        })
        .finally(() => {
            button.disabled = false; // Re-enable button
        });
    }

    /** Helper: Handle Fetch Response */
    function handleFetchResponse(response, context = "data") { if (!response.ok) { return response.json().catch(() => response.text()).then(errDataOrText => { let errMsg = `Server error fetching ${context}: ${response.status}.`; if (typeof errDataOrText === 'string') { errMsg += ` Response: ${errDataOrText}`; } else if (errDataOrText && errDataOrText.error) { errMsg += ` Error: ${errDataOrText.error}`; } throw new Error(errMsg); }); } const contentType = response.headers.get("content-type"); if (contentType && contentType.indexOf("application/json") !== -1) { return response.json(); } else { return response.text().then(text => { console.warn(`Unexpected response format fetching ${context}. Expected JSON. Resp:`, text); throw new Error(`Unexpected response format fetching ${context}.`); }); } }
    /** Helper: Display Error */
    function displayError(message, container) { if(container){container.innerHTML=`<p class="placeholder-text error-text">${message}</p>`}else{console.error("Error container not provided for message:",message)} }
    /** Helper: Escape HTML */
    function escapeHtml(str) { if(str===null||str===undefined)return"";return String(str).replace(/&/g,"&").replace(/</g,"<").replace(/>/g,">").replace(/"/g,"\"").replace(/'/g,"\'") }
    /** Helper: Smooth Scrolling */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => { anchor.addEventListener('click',function(e){const t=this.getAttribute('href');if(t&&t.length>1&&t.startsWith('#')){const n=document.querySelector(t);if(n){e.preventDefault();n.scrollIntoView({behavior:'smooth',block:'start'})}}}); });

    /** Initial Load (if on index page) */
    // Check if the product listing container exists before attempting to load initial products
    if (productListingContainer) {
        loadInitialProducts();
    }

}); // End DOMContentLoaded