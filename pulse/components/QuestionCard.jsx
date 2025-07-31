import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import confetti from 'canvas-confetti';

const QuestionCard = ({ question, onAnswer, onNext }) => {
  const [showConfirmation, setShowConfirmation] = useState(false);
  const [isAnimating, setIsAnimating] = useState(false);

  const handleYes = () => {
    setIsAnimating(true);
    
    // Trigger confetti animation
    const duration = 3 * 1000;
    const animationEnd = Date.now() + duration;
    const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

    function randomInRange(min, max) {
      return Math.random() * (max - min) + min;
    }

    const interval = setInterval(function() {
      const timeLeft = animationEnd - Date.now();

      if (timeLeft <= 0) {
        return clearInterval(interval);
      }

      const particleCount = 50 * (timeLeft / duration);
      // since particles fall down, start a bit higher than random
      confetti({
        ...defaults,
        particleCount,
        origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
      });
      confetti({
        ...defaults,
        particleCount,
        origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
      });
    }, 250);

    // Wait for confetti to finish before moving to next question
    setTimeout(() => {
      onAnswer('yes');
      setIsAnimating(false);
      onNext();
    }, 2000);
  };

  const handleNo = () => {
    setIsAnimating(true);
    // Wait for shatter animation to complete
    setTimeout(() => {
      onAnswer('no');
      setIsAnimating(false);
      onNext();
    }, 1500);
  };

  const handleNotSure = () => {
    setShowConfirmation(true);
  };

  const handleConfirmationYes = () => {
    setIsAnimating(true);
    setShowConfirmation(false);
    // Animate card flying up and out
    setTimeout(() => {
      onAnswer('delegated');
      setIsAnimating(false);
      onNext();
    }, 1000);
  };

  const handleConfirmationNo = () => {
    setShowConfirmation(false);
  };

  // Animation variants for different actions
  const explodeVariants = {
    hidden: { scale: 1, opacity: 1 },
    explode: {
      scale: [1, 1.1, 0],
      opacity: [1, 0.8, 0],
      rotate: [0, 5, -5, 0],
      transition: {
        duration: 1.5,
        times: [0, 0.3, 1],
        ease: "easeOut"
      }
    }
  };

  const shatterVariants = {
    hidden: { scale: 1, opacity: 1, y: 0 },
    shatter: {
      scale: [1, 0.9, 0.1],
      opacity: [1, 0.7, 0],
      y: [0, 50, 400],
      rotate: [0, -10, -45],
      transition: {
        duration: 1.5,
        times: [0, 0.3, 1],
        ease: "easeIn"
      }
    }
  };

  const flyAwayVariants = {
    hidden: { scale: 1, opacity: 1, y: 0 },
    flyAway: {
      scale: [1, 0.8, 0],
      opacity: [1, 0.8, 0],
      y: [0, -100, -500],
      transition: {
        duration: 1,
        ease: "easeOut"
      }
    }
  };

  const getAnimationVariant = () => {
    if (isAnimating) {
      // Determine which animation based on the last action
      // This is a simplified approach - in a real app you'd track the action
      return "explode"; // Default to explode for Yes action
    }
    return "hidden";
  };

  return (
    <div className="question-container">
      <AnimatePresence>
        {!isAnimating && (
          <motion.div
            className="question-card"
            initial={{ scale: 0.8, opacity: 0, y: 50 }}
            animate={{ scale: 1, opacity: 1, y: 0 }}
            exit={
              isAnimating 
                ? shatterVariants.shatter 
                : { scale: 0.8, opacity: 0, y: -50 }
            }
            transition={{ duration: 0.5 }}
          >
            <div className="card-content">
              <p className="info-text">{question.infoText}</p>
              <h2 className="question-text">{question.questionText}</h2>
              
              <div className="button-group">
                <motion.button
                  className="btn btn-no"
                  onClick={handleNo}
                  whileHover={{ scale: 1.05 }}
                  whileTap={{ scale: 0.95 }}
                  disabled={isAnimating}
                >
                  No
                </motion.button>
                
                <motion.button
                  className="btn btn-not-sure"
                  onClick={handleNotSure}
                  whileHover={{ scale: 1.05 }}
                  whileTap={{ scale: 0.95 }}
                  disabled={isAnimating}
                >
                  Not sure
                </motion.button>
                
                <motion.button
                  className="btn btn-yes"
                  onClick={handleYes}
                  whileHover={{ scale: 1.05 }}
                  whileTap={{ scale: 0.95 }}
                  disabled={isAnimating}
                >
                  Yes
                </motion.button>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Confirmation Modal */}
      <AnimatePresence>
        {showConfirmation && (
          <motion.div
            className="modal-overlay"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
          >
            <motion.div
              className="confirmation-modal"
              initial={{ scale: 0.8, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.8, opacity: 0 }}
            >
              <p>Do you want to send this question to someone else?</p>
              <div className="modal-buttons">
                <motion.button
                  className="btn btn-no"
                  onClick={handleConfirmationNo}
                  whileHover={{ scale: 1.05 }}
                  whileTap={{ scale: 0.95 }}
                >
                  No
                </motion.button>
                <motion.button
                  className="btn btn-yes"
                  onClick={handleConfirmationYes}
                  whileHover={{ scale: 1.05 }}
                  whileTap={{ scale: 0.95 }}
                >
                  Yes
                </motion.button>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default QuestionCard;
