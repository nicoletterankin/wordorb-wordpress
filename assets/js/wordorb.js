/**
 * WordOrb WordPress Plugin — Frontend JavaScript
 * Handles interactive quizzes and lesson phase navigation.
 *
 * @version 1.0.0
 */
(function () {
    'use strict';

    /* -----------------------------------------------------------------------
     * Quiz Logic
     * ----------------------------------------------------------------------- */

    function initQuizzes() {
        var quizzes = document.querySelectorAll('.wordorb-quiz');
        quizzes.forEach(function (quiz) {
            var questions  = quiz.querySelectorAll('.wordorb-quiz__question');
            var prevBtn    = quiz.querySelector('.wordorb-quiz__prev');
            var nextBtn    = quiz.querySelector('.wordorb-quiz__next');
            var currentEl  = quiz.querySelector('.wordorb-quiz__current');
            var resultsEl  = quiz.querySelector('.wordorb-quiz__results');
            var scoreEl    = quiz.querySelector('.wordorb-quiz__score');
            var retryBtn   = quiz.querySelector('.wordorb-quiz__retry');
            var navEl      = quiz.querySelector('.wordorb-quiz__nav');
            var total      = questions.length;
            var current    = 0;
            var score      = 0;
            var answered   = {};

            function showQuestion(index) {
                questions.forEach(function (q) {
                    q.classList.remove('wordorb-quiz__question--active');
                });
                if (questions[index]) {
                    questions[index].classList.add('wordorb-quiz__question--active');
                }
                if (currentEl) {
                    currentEl.textContent = index + 1;
                }
                if (prevBtn) {
                    prevBtn.disabled = (index === 0);
                }
                if (nextBtn) {
                    nextBtn.textContent = (index === total - 1) ? 'Finish' : 'Next';
                }
            }

            function checkChoice(questionEl, choiceBtn) {
                var qIndex = parseInt(questionEl.getAttribute('data-index'), 10);
                if (answered[qIndex]) return;
                answered[qIndex] = true;

                var correctAnswer = questionEl.getAttribute('data-answer') || '';
                var selectedValue = choiceBtn.getAttribute('data-value') || '';
                var allChoices    = questionEl.querySelectorAll('.wordorb-quiz__choice');

                allChoices.forEach(function (c) {
                    c.classList.add('wordorb-quiz__choice--disabled');
                    var val = c.getAttribute('data-value') || '';
                    if (val.toUpperCase() === correctAnswer.toUpperCase()) {
                        c.classList.add('wordorb-quiz__choice--correct');
                    }
                });

                if (selectedValue.toUpperCase() === correctAnswer.toUpperCase()) {
                    choiceBtn.classList.add('wordorb-quiz__choice--correct');
                    score++;
                } else {
                    choiceBtn.classList.add('wordorb-quiz__choice--incorrect');
                }

                // Show explanation
                var explanation = questionEl.querySelector('.wordorb-quiz__explanation');
                if (explanation) {
                    explanation.style.display = 'block';
                }
            }

            function showResults() {
                questions.forEach(function (q) {
                    q.classList.remove('wordorb-quiz__question--active');
                });
                if (navEl) navEl.style.display = 'none';
                if (resultsEl) {
                    resultsEl.style.display = 'block';
                }
                if (scoreEl) {
                    var answeredCount = Object.keys(answered).length;
                    scoreEl.textContent = score + ' / ' + answeredCount + ' correct';
                }
            }

            function resetQuiz() {
                score    = 0;
                current  = 0;
                answered = {};

                questions.forEach(function (q) {
                    var choices = q.querySelectorAll('.wordorb-quiz__choice');
                    choices.forEach(function (c) {
                        c.classList.remove(
                            'wordorb-quiz__choice--selected',
                            'wordorb-quiz__choice--correct',
                            'wordorb-quiz__choice--incorrect',
                            'wordorb-quiz__choice--disabled'
                        );
                    });
                    var explanation = q.querySelector('.wordorb-quiz__explanation');
                    if (explanation) {
                        explanation.style.display = 'none';
                    }
                    var textarea = q.querySelector('.wordorb-quiz__open');
                    if (textarea) {
                        textarea.value = '';
                    }
                });

                if (resultsEl) resultsEl.style.display = 'none';
                if (navEl) navEl.style.display = 'flex';
                showQuestion(0);
            }

            // Event: choice buttons
            quiz.addEventListener('click', function (e) {
                var choiceBtn = e.target.closest('.wordorb-quiz__choice');
                if (choiceBtn) {
                    var questionEl = choiceBtn.closest('.wordorb-quiz__question');
                    if (questionEl) {
                        checkChoice(questionEl, choiceBtn);
                    }
                }
            });

            // Event: open-ended submit
            quiz.addEventListener('click', function (e) {
                if (e.target.classList.contains('wordorb-quiz__submit-open')) {
                    var questionEl = e.target.closest('.wordorb-quiz__question');
                    if (questionEl) {
                        var qIndex = parseInt(questionEl.getAttribute('data-index'), 10);
                        if (!answered[qIndex]) {
                            answered[qIndex] = true;
                            score++; // Open-ended always scores (self-assessed)
                            var explanation = questionEl.querySelector('.wordorb-quiz__explanation');
                            if (explanation) {
                                explanation.style.display = 'block';
                            }
                            e.target.disabled = true;
                        }
                    }
                }
            });

            // Event: navigation
            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    if (current > 0) {
                        current--;
                        showQuestion(current);
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    if (current < total - 1) {
                        current++;
                        showQuestion(current);
                    } else {
                        showResults();
                    }
                });
            }

            if (retryBtn) {
                retryBtn.addEventListener('click', resetQuiz);
            }

            // Init
            showQuestion(0);
        });
    }

    /* -----------------------------------------------------------------------
     * Lesson Phase Navigation
     * ----------------------------------------------------------------------- */

    function initLessons() {
        var lessons = document.querySelectorAll('.wordorb-lesson');
        lessons.forEach(function (lesson) {
            var tabs    = lesson.querySelectorAll('.wordorb-lesson__tab');
            var phases  = lesson.querySelectorAll('.wordorb-lesson__phase');
            var prevBtn = lesson.querySelector('.wordorb-lesson__prev');
            var nextBtn = lesson.querySelector('.wordorb-lesson__next');
            var phaseKeys = [];
            var current = 0;

            tabs.forEach(function (tab) {
                phaseKeys.push(tab.getAttribute('data-phase'));
            });

            function showPhase(index) {
                tabs.forEach(function (t) {
                    t.classList.remove('wordorb-lesson__tab--active');
                });
                phases.forEach(function (p) {
                    p.classList.remove('wordorb-lesson__phase--active');
                });

                if (tabs[index]) {
                    tabs[index].classList.add('wordorb-lesson__tab--active');
                }
                var phaseKey = phaseKeys[index];
                var phaseEl  = lesson.querySelector('.wordorb-lesson__phase[data-phase="' + phaseKey + '"]');
                if (phaseEl) {
                    phaseEl.classList.add('wordorb-lesson__phase--active');
                }

                current = index;

                if (prevBtn) prevBtn.disabled = (current === 0);
                if (nextBtn) nextBtn.disabled = (current === phaseKeys.length - 1);
            }

            // Event: tab clicks
            tabs.forEach(function (tab, index) {
                tab.addEventListener('click', function () {
                    showPhase(index);
                });
            });

            // Event: prev/next
            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    if (current > 0) showPhase(current - 1);
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    if (current < phaseKeys.length - 1) showPhase(current + 1);
                });
            }

            // Init
            showPhase(0);
        });
    }

    /* -----------------------------------------------------------------------
     * Initialize on DOMContentLoaded
     * ----------------------------------------------------------------------- */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initQuizzes();
            initLessons();
        });
    } else {
        initQuizzes();
        initLessons();
    }
})();
