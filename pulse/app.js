// Pulse Prototype - Vanilla JavaScript Version
// Dummy data for questions
const questions = [
    {
        id: 1,
        infoText: "This application Lenel S2 is listed as being in the Build Phase, but has a Go-live date in the past.",
        questionText: "Should the status be updated to Complete?",
        correctAction: "yes"
    },
    {
        id: 2,
        infoText: "Application CRM Portal shows no recent activity logs, but is marked as Active in Production.",
        questionText: "Should this application be marked as Inactive?",
        correctAction: "yes"
    },
    {
        id: 3,
        infoText: "The Financial Management System has 15 critical vulnerabilities reported 6 months ago.",
        questionText: "Should we escalate this for immediate security review?",
        correctAction: "yes"
    }
];

// Application state
let currentQuestionIndex = 0;
let answers = [];
let isAnimating = false;

// Swipe tracking variables
let startX = 0;
let startY = 0;
let currentX = 0;
let currentY = 0;
let isSwipeActive = false;
let swipeThreshold = 100; // Minimum distance for swipe detection
let swipeTimeThreshold = 300; // Maximum time for swipe (ms)
let swipeStartTime = 0;

// DOM elements
let questionCard, infoText, questionText, progressText, progressFill, modalOverlay;
let noBtn, notSureBtn, yesBtn, completionScreen, progressIndicator, fullscreenBtn;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    questionCard = document.getElementById('questionCard');
    infoText = document.getElementById('infoText');
    questionText = document.getElementById('questionText');
    progressText = document.getElementById('progressText');
    progressFill = document.getElementById('progressFill');
    modalOverlay = document.getElementById('modalOverlay');
    noBtn = document.getElementById('noBtn');
    notSureBtn = document.getElementById('notSureBtn');
    yesBtn = document.getElementById('yesBtn');
    completionScreen = document.getElementById('completionScreen');
    progressIndicator = document.getElementById('progressIndicator');
    fullscreenBtn = document.getElementById('fullscreenBtn');

    // Check if Font Awesome is loaded and mark buttons accordingly
    checkFontAwesome();

    // Initialize button event listeners for mobile compatibility
    initializeButtonListeners();

    // Initialize fullscreen functionality
    initializeFullscreen();

    // Initialize swipe functionality
    initializeSwipeListeners();

    // Auto-request fullscreen on first user interaction
    enableAutoFullscreen();

    // Load first question
    loadQuestion(currentQuestionIndex);
});

// Check if Font Awesome is loaded
function checkFontAwesome() {
    // Check multiple times with increasing delays since Font Awesome CSS loads asynchronously
    const checkTimes = [100, 500, 1000, 2000];
    
    checkTimes.forEach((delay, index) => {
        setTimeout(() => {
            // Create test element to check if Font Awesome is working
            const testElement = document.createElement('i');
            testElement.className = 'fa-solid fa-thumbs-up';
            testElement.style.position = 'absolute';
            testElement.style.left = '-9999px';
            testElement.style.visibility = 'hidden';
            testElement.style.fontSize = '16px';
            document.body.appendChild(testElement);
            
            // Get computed styles
            const computedStyle = window.getComputedStyle(testElement);
            const beforeStyle = window.getComputedStyle(testElement, ':before');
            
            const fontFamily = computedStyle.getPropertyValue('font-family');
            const content = beforeStyle.getPropertyValue('content');
            const fontWeight = beforeStyle.getPropertyValue('font-weight');
            
            // Check multiple indicators that Font Awesome is loaded
            const isFontAwesomeLoaded = 
                (fontFamily && (fontFamily.includes('Font Awesome') || fontFamily.includes('FontAwesome'))) ||
                (content && content !== 'none' && content !== '""' && content !== "''" && content.length > 2) ||
                (fontWeight && fontWeight === '900');
            
            if (isFontAwesomeLoaded) {
                document.body.classList.add('fa-loaded');
                console.log('Font Awesome loaded successfully');
            } else if (index === checkTimes.length - 1) {
                // Last attempt failed
                console.log('Font Awesome not loaded, using fallback icons');
            }
            
            document.body.removeChild(testElement);
        }, delay);
    });
}

// Audio Context for sound effects
let audioContext = null;

// Initialize Audio Context (must be done after user interaction)
function initializeAudioContext() {
    if (!audioContext) {
        try {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Safari/iOS specific: Resume audio context if suspended
            if (audioContext.state === 'suspended') {
                audioContext.resume().then(() => {
                    console.log('Audio context resumed for Safari/iOS');
                }).catch(e => {
                    console.log('Failed to resume audio context:', e);
                });
            }
        } catch (e) {
            console.log('Web Audio API not supported');
        }
    }
}

// Play swish sound for delegation actions
function playSwishSound() {
    initializeAudioContext();
    
    if (!audioContext) return;
    
    try {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Create a "swish" sound - quick frequency sweep
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(200, audioContext.currentTime + 0.3);
        
        // Volume envelope for smooth swish effect - increased volume for iOS
        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.15, audioContext.currentTime + 0.05); // Increased from 0.1
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.4);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.4);
    } catch (e) {
        console.log('Error playing swish sound:', e);
    }
}

// Play success sound for yes answers
function playSuccessSound() {
    initializeAudioContext();
    
    if (!audioContext) return;
    
    try {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Pleasant ascending tone
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(523, audioContext.currentTime); // C5
        oscillator.frequency.setValueAtTime(659, audioContext.currentTime + 0.1); // E5
        
        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.08, audioContext.currentTime + 0.05); // Increased from 0.05
        gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.2);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2);
    } catch (e) {
        console.log('Error playing success sound:', e);
    }
}

// Play neutral sound for no answers
function playNeutralSound() {
    initializeAudioContext();
    
    if (!audioContext) return;
    
    try {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Single gentle tone
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(440, audioContext.currentTime); // A4
        
        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.05, audioContext.currentTime + 0.05); // Increased from 0.03
        gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.15);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.15);
    } catch (e) {
        console.log('Error playing neutral sound:', e);
    }
}

// Mobile device detection
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
           (window.innerWidth <= 768 && 'ontouchstart' in window);
}

// Fullscreen functionality
function initializeFullscreen() {
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', toggleFullscreen);
        fullscreenBtn.addEventListener('touchstart', toggleFullscreen, { passive: true });
    }
    
    // Listen for fullscreen changes
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);
}

function toggleFullscreen() {
    initializeAudioContext(); // Initialize audio on user interaction
    
    if (!document.fullscreenElement && 
        !document.webkitFullscreenElement && 
        !document.mozFullScreenElement && 
        !document.msFullscreenElement) {
        enterFullscreen();
    } else {
        exitFullscreen();
    }
}

function enterFullscreen() {
    const element = document.documentElement;
    
    if (element.requestFullscreen) {
        element.requestFullscreen();
    } else if (element.webkitRequestFullscreen) {
        element.webkitRequestFullscreen();
    } else if (element.mozRequestFullScreen) {
        element.mozRequestFullScreen();
    } else if (element.msRequestFullscreen) {
        element.msRequestFullscreen();
    }
}

function exitFullscreen() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
    } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
    } else if (document.msExitFullscreen) {
        document.msExitFullscreen();
    }
}

function handleFullscreenChange() {
    const isFullscreen = !!(document.fullscreenElement || 
                           document.webkitFullscreenElement || 
                           document.mozFullScreenElement || 
                           document.msFullscreenElement);
    
    if (isFullscreen) {
        document.body.classList.add('fullscreen-active');
        if (fullscreenBtn) {
            fullscreenBtn.innerHTML = '<i class="fa-solid fa-compress"></i><span class="fallback-icon">⛶</span>';
            fullscreenBtn.title = 'Exit Fullscreen';
        }
    } else {
        document.body.classList.remove('fullscreen-active');
        if (fullscreenBtn) {
            fullscreenBtn.innerHTML = '<i class="fa-solid fa-expand"></i><span class="fallback-icon">⛶</span>';
            fullscreenBtn.title = 'Enter Fullscreen';
        }
    }
}

function showFullscreenPrompt() {
    if (isMobileDevice() && !document.fullscreenElement) {
        // Create a subtle prompt
        const prompt = document.createElement('div');
        prompt.className = 'fullscreen-prompt';
        prompt.innerHTML = `
            <div class="prompt-content">
                <span class="prompt-text">💡 Tip: Tap the ⛶ button for fullscreen experience</span>
                <button class="prompt-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        document.body.appendChild(prompt);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (prompt.parentElement) {
                prompt.remove();
            }
        }, 5000);
    }
}

// Auto fullscreen setup
let autoFullscreenEnabled = false;

function enableAutoFullscreen() {
    autoFullscreenEnabled = true;
    
    // Add event listeners for first user interaction
    const firstInteractionEvents = ['click', 'touchstart', 'keydown', 'mousemove'];
    
    function handleFirstInteraction() {
        if (autoFullscreenEnabled) {
            autoFullscreenEnabled = false; // Only trigger once
            
            // Remove event listeners
            firstInteractionEvents.forEach(event => {
                document.removeEventListener(event, handleFirstInteraction, true);
            });
            
            // Small delay to ensure the current event is processed
            setTimeout(() => {
                enterFullscreen();
            }, 100);
        }
    }
    
    // Add listeners for all interaction types
    firstInteractionEvents.forEach(event => {
        document.addEventListener(event, handleFirstInteraction, true);
    });
    
    // Show a subtle notification about auto-fullscreen
    showAutoFullscreenNotification();
}

function showAutoFullscreenNotification() {
    const notification = document.createElement('div');
    notification.className = 'auto-fullscreen-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">⚡</span>
            <span class="notification-text">Tap anywhere to enter fullscreen mode</span>
        </div>
    `;
    document.body.appendChild(notification);
    
    // Auto-remove when fullscreen is activated or after 8 seconds
    const removeNotification = () => {
        if (notification.parentElement) {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }
    };
    
    // Remove on fullscreen change
    const fullscreenHandler = () => {
        removeNotification();
        document.removeEventListener('fullscreenchange', fullscreenHandler);
        document.removeEventListener('webkitfullscreenchange', fullscreenHandler);
    };
    
    document.addEventListener('fullscreenchange', fullscreenHandler);
    document.addEventListener('webkitfullscreenchange', fullscreenHandler);
    
    // Auto-remove after 8 seconds
    setTimeout(removeNotification, 8000);
}

// Load a specific question
function loadQuestion(index) {
    if (index >= questions.length) {
        showCompletionScreen();
        return;
    }

    const question = questions[index];
    
    // Update content
    infoText.textContent = question.infoText;
    questionText.textContent = question.questionText;
    
    // Update progress
    progressText.textContent = `Question ${index + 1} of ${questions.length}`;
    progressFill.style.width = `${((index + 1) / questions.length) * 100}%`;
    
    // Reset card animation and styles
    questionCard.className = 'question-card';
    questionCard.style.display = 'block';
    questionCard.style.transform = 'scale(0.8) translateY(50px)';
    questionCard.style.opacity = '0';
    questionCard.style.transition = '';
    questionCard.style.borderColor = '#e5e7eb';
    
    // Re-initialize button listeners for the new card
    initializeButtonListeners();
    
    // Re-initialize swipe listeners for the new card
    initializeSwipeListeners();
    
    // Enable buttons
    enableButtons();
    
    // Animate card in
    setTimeout(() => {
        questionCard.style.transition = 'all 0.5s ease';
        questionCard.style.opacity = '1';
        questionCard.style.transform = 'scale(1) translateY(0)';
    }, 100);
}

// Handle answer selection
function handleAnswer(answer) {
    // Use the new swipe-aware function, detecting if it's from swipe
    const isSwipe = !event || (event.type && (event.type.includes('touch') || event.type.includes('mouse')));
    handleAnswerWithSwipe(answer, false); // Always use button animation for explicit button clicks
}

// Handle "Yes" answer with confetti
function handleYesAnswer() {
    // Play success sound for yes answer
    playSuccessSound();
    
    // Trigger spectacular confetti animation
    const duration = 3 * 1000;
    const animationEnd = Date.now() + duration;
    const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 1000 };

    function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    // Initial burst of confetti
    confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 }
    });

    // Continuous confetti rain
    const interval = setInterval(function() {
        const timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
            return clearInterval(interval);
        }

        const particleCount = 50 * (timeLeft / duration);
        
        // Left side confetti
        confetti({
            ...defaults,
            particleCount,
            origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
        });
        
        // Right side confetti
        confetti({
            ...defaults,
            particleCount,
            origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
        });
        
        // Center burst occasionally
        if (Math.random() < 0.3) {
            confetti({
                particleCount: 30,
                spread: 60,
                origin: { x: 0.5, y: 0.3 }
            });
        }
    }, 250);

    // Explode animation for the card
    questionCard.style.transition = 'all 1.5s ease-out';
    questionCard.style.transform = 'scale(1.1) rotate(5deg)';
    questionCard.style.opacity = '0.8';
    
    setTimeout(() => {
        questionCard.style.transform = 'scale(0) rotate(-5deg)';
        questionCard.style.opacity = '0';
    }, 500);

    // Move to next question after animation
    setTimeout(() => {
        nextQuestion();
    }, 2000);
}

// Handle "Yes" answer with swipe right animation
function handleYesAnswerSwipe() {
    // Play success sound for yes answer
    playSuccessSound();
    
    // Trigger confetti animation
    confetti({
        particleCount: 80,
        spread: 70,
        origin: { y: 0.6 }
    });

    // Swipe right animation for the card
    questionCard.style.transition = 'all 0.8s ease-out';
    questionCard.style.transform = 'translateX(150%) rotate(20deg)';
    questionCard.style.opacity = '0';

    // Move to next question after animation
    setTimeout(() => {
        nextQuestion();
    }, 800);
}

// Handle "No" answer with shatter effect
function handleNoAnswer() {
    // Play neutral sound for no answer
    playNeutralSound();
    
    // Shatter animation
    questionCard.style.transition = 'all 1.5s ease-in';
    questionCard.style.transform = 'scale(0.9) translateY(50px) rotate(-10deg)';
    questionCard.style.opacity = '0.7';
    
    setTimeout(() => {
        questionCard.style.transform = 'scale(0.1) translateY(400px) rotate(-45deg)';
        questionCard.style.opacity = '0';
    }, 450);

    // Move to next question after animation
    setTimeout(() => {
        nextQuestion();
    }, 1500);
}

// Handle "No" answer with swipe left animation
function handleNoAnswerSwipe() {
    // Play neutral sound for no answer
    playNeutralSound();
    
    // Swipe left animation for the card
    questionCard.style.transition = 'all 0.8s ease-out';
    questionCard.style.transform = 'translateX(-150%) rotate(-20deg)';
    questionCard.style.opacity = '0';

    // Move to next question after animation
    setTimeout(() => {
        nextQuestion();
    }, 800);
}

// Handle "Delegated" answer with fly away effect
function handleDelegatedAnswer() {
    // Play swish sound for delegation
    playSwishSound();
    
    // Fly away animation
    questionCard.style.transition = 'all 1s ease-out';
    questionCard.style.transform = 'scale(0.8) translateY(-100px)';
    questionCard.style.opacity = '0.8';
    
    setTimeout(() => {
        questionCard.style.transform = 'scale(0) translateY(-500px)';
        questionCard.style.opacity = '0';
    }, 300);

    // Move to next question after animation
    setTimeout(() => {
        nextQuestion();
    }, 1000);
}

// Handle "Delegated" answer with swipe up animation
function handleDelegatedAnswerSwipe() {
    // Play swish sound for delegation
    playSwishSound();
    
    // Swipe up animation for the card
    questionCard.style.transition = 'all 0.8s ease-out';
    questionCard.style.transform = 'translateY(-150%) scale(0.8)';
    questionCard.style.opacity = '0';

    // Move to next question after animation
    setTimeout(() => {
        nextQuestion();
    }, 800);
}

// Show confirmation modal for "Not sure"
function showConfirmationModal() {
    modalOverlay.style.display = 'flex';
    modalOverlay.style.opacity = '0';
    
    setTimeout(() => {
        modalOverlay.style.transition = 'opacity 0.3s ease';
        modalOverlay.style.opacity = '1';
    }, 10);
}

// Handle confirmation modal "No"
function handleConfirmationNo() {
    modalOverlay.style.opacity = '0';
    setTimeout(() => {
        modalOverlay.style.display = 'none';
        isAnimating = false;
        enableButtons();
    }, 300);
}

// Handle confirmation modal "Yes"
function handleConfirmationYes() {
    modalOverlay.style.opacity = '0';
    setTimeout(() => {
        modalOverlay.style.display = 'none';
        // Update the last answer to "delegated"
        answers[answers.length - 1].answer = 'delegated';
        handleDelegatedAnswer();
    }, 300);
}

// Move to next question
function nextQuestion() {
    currentQuestionIndex++;
    isAnimating = false;
    
    if (currentQuestionIndex < questions.length) {
        loadQuestion(currentQuestionIndex);
    } else {
        showCompletionScreen();
    }
}

// Show completion screen
function showCompletionScreen() {
    progressIndicator.style.display = 'none';
    questionCard.style.display = 'none';
    completionScreen.style.display = 'block';
    
    // Update statistics
    const yesCount = answers.filter(a => a.answer === 'yes').length;
    const noCount = answers.filter(a => a.answer === 'no').length;
    const delegatedCount = answers.filter(a => a.answer === 'delegated').length;
    
    document.getElementById('totalAnswers').textContent = answers.length;
    document.getElementById('yesCount').textContent = yesCount;
    document.getElementById('noCount').textContent = noCount;
    document.getElementById('delegatedCount').textContent = delegatedCount;
    
    // Animate completion screen in
    completionScreen.style.opacity = '0';
    completionScreen.style.transform = 'translateY(50px)';
    
    setTimeout(() => {
        completionScreen.style.transition = 'all 0.5s ease';
        completionScreen.style.opacity = '1';
        completionScreen.style.transform = 'translateY(0)';
    }, 100);
}

// Reset to start over
function resetQuestions() {
    currentQuestionIndex = 0;
    answers = [];
    isAnimating = false;
    
    // Reset UI
    completionScreen.style.display = 'none';
    progressIndicator.style.display = 'block';
    
    // Load first question
    loadQuestion(0);
}

// Utility functions
function enableButtons() {
    noBtn.disabled = false;
    notSureBtn.disabled = false;
    yesBtn.disabled = false;
}

function disableButtons() {
    noBtn.disabled = true;
    notSureBtn.disabled = true;
    yesBtn.disabled = true;
}

// Haptic feedback function for mobile devices
function triggerHapticFeedback(answer) {
    let hapticTriggered = false;
    
    // 1. Try iOS Haptic Feedback (iOS 10+)
    if (window.navigator && window.navigator.userAgent.includes('iPhone')) {
        try {
            // iOS Haptic Engine (if available)
            if (window.AudioContext || window.webkitAudioContext) {
                // Create short audio blip as haptic substitute on iOS
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                
                if (answer === 'yes') {
                    // Success: Two quick beeps
                    playIOSHapticBeep(audioContext, 800, 50);
                    setTimeout(() => playIOSHapticBeep(audioContext, 1000, 50), 100);
                } else if (answer === 'no') {
                    // Warning: Lower tone
                    playIOSHapticBeep(audioContext, 400, 150);
                }
                hapticTriggered = true;
            }
        } catch (e) {
            console.log('iOS haptic fallback failed:', e);
        }
    }
    
    // 2. Android/Chrome vibration API
    if (!hapticTriggered && 'vibrate' in navigator) {
        let vibrationPattern;
        
        if (answer === 'yes') {
            // Success pattern: double tap - short, pause, short
            vibrationPattern = [80, 40, 80];
        } else if (answer === 'no') {
            // Warning pattern: single medium vibration
            vibrationPattern = [150];
        }
        
        // Trigger vibration
        try {
            navigator.vibrate(vibrationPattern);
            hapticTriggered = true;
        } catch (e) {
            console.log('Vibration not supported on this device');
        }
    }
    
    // 3. Enhanced feedback for gamepad controllers
    if ('getGamepads' in navigator) {
        const gamepads = navigator.getGamepads();
        for (let gamepad of gamepads) {
            if (gamepad && gamepad.vibrationActuator) {
                if (answer === 'yes') {
                    // Success vibration: light and quick
                    gamepad.vibrationActuator.playEffect('dual-rumble', {
                        duration: 200,
                        strongMagnitude: 0.3,
                        weakMagnitude: 0.5
                    });
                } else if (answer === 'no') {
                    // Warning vibration: stronger and longer
                    gamepad.vibrationActuator.playEffect('dual-rumble', {
                        duration: 300,
                        strongMagnitude: 0.7,
                        weakMagnitude: 0.4
                    });
                }
                hapticTriggered = true;
            }
        }
    }
    
    // 4. Fallback visual feedback for devices without haptic support
    if (!hapticTriggered) {
        // Add a subtle visual pulse effect to the button
        const button = answer === 'yes' ? yesBtn : noBtn;
        
        // Enhanced visual feedback with color flash
        const originalColor = button.style.backgroundColor;
        const originalTransform = button.style.transform;
        
        if (answer === 'yes') {
            button.style.backgroundColor = '#22c55e'; // Bright green flash
            button.style.transform = 'scale(0.95)';
        } else {
            button.style.backgroundColor = '#ef4444'; // Bright red flash
            button.style.transform = 'scale(0.95)';
        }
        
        setTimeout(() => {
            button.style.backgroundColor = originalColor;
            button.style.transform = originalTransform;
        }, 150);
    }
}

// Helper function for iOS haptic audio feedback
function playIOSHapticBeep(audioContext, frequency, duration) {
    try {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(frequency, audioContext.currentTime);
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + duration / 1000);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + duration / 1000);
    } catch (e) {
        console.log('Audio haptic feedback failed:', e);
    }
}

// Initialize button event listeners for mobile compatibility
function initializeButtonListeners() {
    if (noBtn) {
        // Remove onclick attributes and add proper event listeners
        noBtn.removeAttribute('onclick');
        noBtn.addEventListener('touchstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
            initializeAudioContext(); // Initialize audio on first user interaction
            handleAnswer('no');
        }, { passive: false });
        noBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            initializeAudioContext(); // Initialize audio on first user interaction
            handleAnswer('no');
        });
    }
    
    if (notSureBtn) {
        notSureBtn.removeAttribute('onclick');
        notSureBtn.addEventListener('touchstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
            initializeAudioContext(); // Initialize audio on first user interaction
            handleAnswer('not-sure');
        }, { passive: false });
        notSureBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            initializeAudioContext(); // Initialize audio on first user interaction
            handleAnswer('not-sure');
        });
    }
    
    if (yesBtn) {
        yesBtn.removeAttribute('onclick');
        yesBtn.addEventListener('touchstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
            initializeAudioContext(); // Initialize audio on first user interaction
            handleAnswer('yes');
        }, { passive: false });
        yesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            initializeAudioContext(); // Initialize audio on first user interaction
            handleAnswer('yes');
        });
    }
}

// Initialize swipe listeners
function initializeSwipeListeners() {
    if (!questionCard) return;

    // Touch events for mobile
    questionCard.addEventListener('touchstart', handleSwipeStart, { passive: false });
    questionCard.addEventListener('touchmove', handleSwipeMove, { passive: false });
    questionCard.addEventListener('touchend', handleSwipeEnd, { passive: false });

    // Mouse events for desktop (optional)
    questionCard.addEventListener('mousedown', handleSwipeStart);
    questionCard.addEventListener('mousemove', handleSwipeMove);
    questionCard.addEventListener('mouseup', handleSwipeEnd);
    questionCard.addEventListener('mouseleave', handleSwipeEnd);
}

// Handle swipe/drag start
function handleSwipeStart(e) {
    if (isAnimating) return;
    
    e.preventDefault();
    isSwipeActive = true;
    swipeStartTime = Date.now();
    
    const touch = e.touches ? e.touches[0] : e;
    startX = touch.clientX;
    startY = touch.clientY;
    currentX = startX;
    currentY = startY;
    
    // Add a subtle scale effect to show the card is active
    questionCard.style.transition = 'transform 0.1s ease';
    questionCard.style.transform = 'scale(0.98)';
}

// Handle swipe/drag movement
function handleSwipeMove(e) {
    if (!isSwipeActive || isAnimating) return;
    
    e.preventDefault();
    const touch = e.touches ? e.touches[0] : e;
    currentX = touch.clientX;
    currentY = touch.clientY;
    
    const deltaX = currentX - startX;
    const deltaY = currentY - startY;
    
    // Calculate rotation based on horizontal movement
    const rotation = deltaX * 0.1;
    
    // Visual feedback during swipe
    questionCard.style.transition = 'none';
    questionCard.style.transform = `translate(${deltaX * 0.3}px, ${deltaY * 0.3}px) rotate(${rotation}deg) scale(0.98)`;
    
    // Add opacity feedback based on swipe distance
    const maxDistance = 200;
    const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
    const opacity = Math.max(0.7, 1 - (distance / maxDistance) * 0.3);
    questionCard.style.opacity = opacity;
    
    // Visual hint for swipe direction
    if (Math.abs(deltaX) > Math.abs(deltaY)) {
        // Horizontal swipe
        if (deltaX > 50) {
            questionCard.style.borderColor = '#22c55e'; // Green for Yes
        } else if (deltaX < -50) {
            questionCard.style.borderColor = '#ef4444'; // Red for No
        } else {
            questionCard.style.borderColor = '#e5e7eb'; // Default
        }
    } else if (deltaY < -50) {
        // Upward swipe
        questionCard.style.borderColor = '#3b82f6'; // Blue for Delegate
    } else {
        questionCard.style.borderColor = '#e5e7eb'; // Default
    }
}

// Handle swipe/drag end
function handleSwipeEnd(e) {
    if (!isSwipeActive) return;
    
    isSwipeActive = false;
    const swipeTime = Date.now() - swipeStartTime;
    
    const deltaX = currentX - startX;
    const deltaY = currentY - startY;
    const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
    
    // Reset visual feedback
    questionCard.style.borderColor = '#e5e7eb';
    questionCard.style.opacity = '1';
    
    // Check if it's a valid swipe (distance and time thresholds)
    if (distance > swipeThreshold && swipeTime < swipeTimeThreshold) {
        // Determine swipe direction
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            // Horizontal swipe
            if (deltaX > 0) {
                // Swipe right = Yes
                handleAnswerWithSwipe('yes', true);
                return;
            } else {
                // Swipe left = No
                handleAnswerWithSwipe('no', true);
                return;
            }
        } else if (deltaY < 0) {
            // Swipe up = Delegate
            handleAnswerWithSwipe('not-sure', true);
            return;
        }
    }
    
    // Not a valid swipe - animate back to original position
    questionCard.style.transition = 'all 0.3s ease';
    questionCard.style.transform = 'scale(1)';
}

// Modified handleAnswer to support swipe animations
function handleAnswerWithSwipe(answer, isSwipe = false) {
    if (isAnimating) return;
    
    // Initialize audio context on user interaction
    initializeAudioContext();
    
    // Add haptic feedback for Yes and No buttons on mobile
    if (answer === 'yes' || answer === 'no') {
        triggerHapticFeedback(answer);
    }
    
    isAnimating = true;
    disableButtons();
    
    // Record answer
    const answerData = {
        questionId: questions[currentQuestionIndex].id,
        answer: answer,
        timestamp: new Date().toISOString(),
        method: isSwipe ? 'swipe' : 'button'
    };
    answers.push(answerData);
    
    // Handle different answer types with appropriate animations
    switch(answer) {
        case 'yes':
            if (isSwipe) {
                handleYesAnswerSwipe();
            } else {
                handleYesAnswer();
            }
            break;
        case 'no':
            if (isSwipe) {
                handleNoAnswerSwipe();
            } else {
                handleNoAnswer();
            }
            break;
        case 'not-sure':
            if (isSwipe) {
                // For swipe up, directly delegate without modal
                answers[answers.length - 1].answer = 'delegated';
                handleDelegatedAnswerSwipe();
            } else {
                showConfirmationModal();
            }
            break;
        case 'delegated':
            if (isSwipe) {
                handleDelegatedAnswerSwipe();
            } else {
                handleDelegatedAnswer();
            }
            break;
    }
}

// Add hover effects using JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.btn');
    
    buttons.forEach(button => {
        // Desktop hover effects
        button.addEventListener('mouseenter', function() {
            if (!this.disabled && !('ontouchstart' in window)) {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            if (!this.disabled && !('ontouchstart' in window)) {
                this.style.transform = 'translateY(0) scale(1)';
            }
        });
        
        // Touch feedback for mobile
        button.addEventListener('touchstart', function() {
            if (!this.disabled) {
                this.style.transform = 'scale(0.95)';
            }
        }, { passive: true });
        
        button.addEventListener('touchend', function() {
            if (!this.disabled) {
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 100);
            }
        }, { passive: true });
        
        // Mouse press effects for desktop
        button.addEventListener('mousedown', function() {
            if (!this.disabled && !('ontouchstart' in window)) {
                this.style.transform = 'translateY(0) scale(0.95)';
            }
        });
        
        button.addEventListener('mouseup', function() {
            if (!this.disabled && !('ontouchstart' in window)) {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            }
        });
    });
});
