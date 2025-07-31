import React, { useState } from 'react';
import { AnimatePresence } from 'framer-motion';
import QuestionCard from './components/QuestionCard.jsx';
import { questions } from './data/questions.js';
import './styles.css';

function App() {
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [answers, setAnswers] = useState([]);
  const [isComplete, setIsComplete] = useState(false);

  const handleAnswer = (answer) => {
    const newAnswer = {
      questionId: questions[currentQuestionIndex].id,
      answer: answer,
      timestamp: new Date().toISOString()
    };
    setAnswers(prev => [...prev, newAnswer]);
  };

  const handleNext = () => {
    if (currentQuestionIndex < questions.length - 1) {
      setCurrentQuestionIndex(currentQuestionIndex + 1);
    } else {
      setIsComplete(true);
    }
  };

  const resetQuestions = () => {
    setCurrentQuestionIndex(0);
    setAnswers([]);
    setIsComplete(false);
  };

  if (isComplete) {
    return (
      <div className="app">
        <header className="app-header">
          <h1 className="logo">AppTrack</h1>
        </header>
        
        <main className="app-main">
          <div className="completion-screen">
            <h2>All questions completed!</h2>
            <p>Thank you for your input. Your responses have been recorded.</p>
            <div className="completion-stats">
              <p>Questions answered: {answers.length}</p>
              <p>Yes: {answers.filter(a => a.answer === 'yes').length}</p>
              <p>No: {answers.filter(a => a.answer === 'no').length}</p>
              <p>Delegated: {answers.filter(a => a.answer === 'delegated').length}</p>
            </div>
            <button className="btn btn-primary" onClick={resetQuestions}>
              Start Over
            </button>
          </div>
        </main>
      </div>
    );
  }

  return (
    <div className="app">
      <header className="app-header">
        <h1 className="logo">AppTrack</h1>
      </header>
      
      <main className="app-main">
        <div className="progress-indicator">
          <span className="progress-text">
            Question {currentQuestionIndex + 1} of {questions.length}
          </span>
          <div className="progress-bar">
            <div 
              className="progress-fill"
              style={{ width: `${((currentQuestionIndex + 1) / questions.length) * 100}%` }}
            />
          </div>
        </div>
        
        <AnimatePresence mode="wait">
          <QuestionCard
            key={currentQuestionIndex}
            question={questions[currentQuestionIndex]}
            onAnswer={handleAnswer}
            onNext={handleNext}
          />
        </AnimatePresence>
      </main>
    </div>
  );
}

export default App;
