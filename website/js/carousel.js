// Modal Photo View with Carousel Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Create modal elements
    const modalContainer = document.createElement('div');
    modalContainer.className = 'photo-modal-container';
    modalContainer.style.display = 'none';
    
    const modalContent = `
        <div class="photo-modal-content">
            <button class="modal-close-btn">x</button>
            <div class="modal-main-image-container">
                <button class="modal-nav-btn prev-btn">&#10094;</button>
                <img src="" alt="" class="modal-main-image">
                <button class="modal-nav-btn next-btn">&#10095;</button>
            </div>
            <div class="modal-thumbnails-container"></div>
        </div>
    `;
    
    modalContainer.innerHTML = modalContent;
    document.body.appendChild(modalContainer);
    
    // Add styles for modal
    const modalStyles = document.createElement('style');
    modalStyles.textContent = `
        .photo-modal-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .photo-modal-container.show {
            opacity: 1;
        }
        
        .photo-modal-content {
            width: 90%;
            height: 90%;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 2001;
        }
        
        .modal-close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 30px;
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            z-index: 2010;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-close-btn:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }
        
        .modal-main-image-container {
            height: 80%;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .modal-main-image {
            max-width: 80%;
            max-height: 100%;
            object-fit: contain;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .modal-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: background 0.3s;
            z-index: 2002;
        }
        
        .modal-nav-btn:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        
        .prev-btn {
            left: 20px;
        }
        
        .next-btn {
            right: 20px;
        }
        
        .modal-thumbnails-container {
            height: 20%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 10px;
            overflow-x: auto;
        }
        
        .modal-thumbnail {
            height: 70px;
            width: auto;
            border: 2px solid transparent;
            opacity: 0.7;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .modal-thumbnail:hover {
            opacity: 1;
        }
        
        .modal-thumbnail.active {
            border-color: white;
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .modal-main-image {
                max-width: 95%;
            }
            
            .modal-thumbnail {
                height: 50px;
            }
            
            .modal-nav-btn {
                width: 30px;
                height: 30px;
                font-size: 16px;
            }
        }
    `;
    document.head.appendChild(modalStyles);
    
    // Get modal elements
    const modal = document.querySelector('.photo-modal-container');
    const closeBtn = modal.querySelector('.modal-close-btn');
    const mainImage = modal.querySelector('.modal-main-image');
    const prevBtn = modal.querySelector('.prev-btn');
    const nextBtn = modal.querySelector('.next-btn');
    const thumbnailsContainer = modal.querySelector('.modal-thumbnails-container');
    
    // Variables to track current images
    let currentImages = [];
    let currentIndex = 0;
    
    // Function to initialize photo sections
    function initializePhotoSections() {
        // Define sections to apply modal to
        const photoSections = [
            {
                container: '.offers-wrapper',
                itemSelector: '.offer-slide img'
            },
            {
                container: '.product-container',
                itemSelector: '.product-image'
            },
            {
                container: '.image-container',
                itemSelector: '.image-container img'
            },
            {
                container: '.staff-container',
                itemSelector: '.image-staff img'
            }
        ];
        
        // Add click listeners to each section
        photoSections.forEach(section => {
            const container = document.querySelector(section.container);
            if (container) {
                const images = container.querySelectorAll(section.itemSelector);
                
                images.forEach((img, index) => {
                    img.style.cursor = 'pointer';
                    img.addEventListener('click', function() {
                        openModal(images, index);
                    });
                });
            }
        });
    }
    
    // Function to open modal
    function openModal(images, selectedIndex) {
        currentImages = Array.from(images);
        currentIndex = selectedIndex;
        
        // Set main image
        updateMainImage();
        
        // Create thumbnails
        createThumbnails();
        
        // Show modal with animation
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        
        // Prevent body scrolling
        document.body.style.overflow = 'hidden';
    }
    
    // Function to close modal
    function closeModal() {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            // Clear thumbnails
            thumbnailsContainer.innerHTML = '';
        }, 300);
        
        // Re-enable body scrolling
        document.body.style.overflow = '';
    }
    
    // Function to update main image
    function updateMainImage() {
        const currentImg = currentImages[currentIndex];
        mainImage.src = currentImg.src;
        mainImage.alt = currentImg.alt || 'Gallery Image';
        
        // Update active thumbnail
        const thumbnails = thumbnailsContainer.querySelectorAll('.modal-thumbnail');
        thumbnails.forEach((thumb, idx) => {
            if (idx === currentIndex) {
                thumb.classList.add('active');
            } else {
                thumb.classList.remove('active');
            }
        });
    }
    
    // Function to create thumbnails
    function createThumbnails() {
        thumbnailsContainer.innerHTML = '';
        
        currentImages.forEach((img, idx) => {
            const thumbnail = document.createElement('img');
            thumbnail.src = img.src;
            thumbnail.alt = 'Thumbnail';
            thumbnail.className = 'modal-thumbnail';
            if (idx === currentIndex) {
                thumbnail.classList.add('active');
            }
            
            thumbnail.addEventListener('click', function() {
                currentIndex = idx;
                updateMainImage();
            });
            
            thumbnailsContainer.appendChild(thumbnail);
        });
    }
    
    // Next image function
    function nextImage() {
        currentIndex = (currentIndex + 1) % currentImages.length;
        updateMainImage();
    }
    
    // Previous image function
    function prevImage() {
        currentIndex = (currentIndex - 1 + currentImages.length) % currentImages.length;
        updateMainImage();
    }
    
    // Event listeners
    closeBtn.addEventListener('click', closeModal);
    prevBtn.addEventListener('click', prevImage);
    nextBtn.addEventListener('click', nextImage);
    
    // Close modal when clicking outside of content
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (modal.style.display === 'flex') {
            if (e.key === 'Escape') {
                closeModal();
            } else if (e.key === 'ArrowRight') {
                nextImage();
            } else if (e.key === 'ArrowLeft') {
                prevImage();
            }
        }
    });
    
    // Initialize all photo sections
    initializePhotoSections();
});