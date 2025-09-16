@extends('layouts.user')

@push('head')
<!-- Prevent indexing and caching -->
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<!-- MathJax Configuration -->
<script>
    window.MathJax = {
        tex: {
            inlineMath: [['\\(', '\\)'], ['$', '$']],
            displayMath: [['\\[', '\\]'], ['$$', '$$']],
            processEscapes: true,
            processEnvironments: true
        },
        options: {
            skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
        },
        startup: {
            ready: () => {
                MathJax.startup.defaultReady();
            }
        }
    };
</script>

<script type="text/javascript" id="MathJax-script" async
        src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js">
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    .credit-warning {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .question-card-enter {
        animation: slideIn 0.3s ease-out;
    }
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    .badge-animate {
        transition: all 0.2s ease-in-out;
    }
    .badge-animate:hover {
        transform: scale(1.05);
    }
    
    /* Prevent text selection and copying */
    .no-select {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    
    .question-content {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
        pointer-events: auto;
    }
    
    /* Image styling with 350px width */
    .question-content img {
        -webkit-user-drag: none;
        -khtml-user-drag: none;
        -moz-user-drag: none;
        -o-user-drag: none;
        user-drag: none;
        pointer-events: none;
        width: 350px;
        height: auto;
        max-width: 100%;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin: 10px 0;
        display: block;
    }
    
    /* Responsive image sizing */
    @media screen and (max-width: 480px) {
        .question-content img {
            width: 100%;
            max-width: 350px;
        }
    }
    
    /* Blur effect when trying to inspect */
    .protected-content {
        position: relative;
    }
    
    .protected-content::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1;
        pointer-events: none;
        transition: all 0.3s ease;
    }
    
    /* Hide scrollbars to prevent content inspection */
    .question-content::-webkit-scrollbar {
        display: none;
    }
    
    .question-content {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    
    /* Layout optimizations for better space usage */
    .question-container {
        max-width: 100%;
        margin: 0 auto;
    }
    
    /* Optimize for 80% zoom - ensure text remains readable */
    @media screen and (min-width: 1200px) {
        .question-card {
            font-size: 1.05rem;
            line-height: 1.6;
        }
        
        .question-metadata {
            font-size: 0.95rem;
        }
        
        .mathjax-content {
            font-size: 1.1rem;
            line-height: 1.7;
        }
        
        /* At 80% zoom, make content more readable */
        .question-card h3 {
            font-size: 1.75rem;
        }
        
        .question-content-section {
            min-height: auto;
        }
        
        /* Better badge sizing at smaller zooms */
        .badge-animate {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }
    }
    
    /* Optimize for very large screens */
    @media screen and (min-width: 1920px) {
        .question-container {
            max-width: 1600px;
        }
        
        .mathjax-content {
            font-size: 1.2rem;
            line-height: 1.8;
        }
    }
    
    /* Ensure proper spacing and prevent content overflow */
    .question-card {
        overflow: hidden;
        min-height: fit-content;
    }
    
    /* Improve metadata badge responsiveness */
    .metadata-grid .badge-animate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px;
    }
    
    /* Better responsive design for the navigation */
    .navigation-container .flex {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    @media screen and (max-width: 640px) {
        .navigation-container .flex {
            flex-direction: column;
            align-items: center;
        }
    }
    
    /* Improve spacing for metadata badges */
    .metadata-grid {
        gap: 0.75rem;
    }
    
    @media screen and (max-width: 768px) {
        .metadata-grid {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
    }
    
    /* Better spacing for navigation */
    .navigation-container {
        padding: 1rem 1.5rem;
    }
    
    /* Optimize question content padding */
    .question-content-section {
        padding: 1.5rem;
    }
    
    @media screen and (min-width: 1024px) {
        .question-content-section {
            padding: 2rem;
        }
    }
</style>
@endpush

@section('content')

<!-- Background with gradient -->
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <div class="container mx-auto px-4 py-4 max-w-full">

        <!-- Stats Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Total Questions -->
            <div class="bg-white rounded-2xl shadow-lg p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-xl mr-4">
                        <i class="fas fa-list text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Questions</p>
                        <p class="text-2xl font-bold text-gray-800" id="totalQuestions">{{ count($questions) }}</p>
                    </div>
                </div>
            </div>

            <!-- Selected Questions -->
            <div class="bg-white rounded-2xl shadow-lg p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-xl mr-4">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Selected</p>
                        <p class="text-2xl font-bold text-green-600" id="selectedCount">0</p>
                    </div>
                </div>
            </div>

            <!-- Credits Required -->
            <div class="bg-white rounded-2xl shadow-lg p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-xl mr-4">
                        <i class="fas fa-coins text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Credits Needed</p>
                        <p class="text-2xl font-bold text-yellow-600" id="creditsNeeded">0</p>
                    </div>
                </div>
            </div>

            <!-- Available Credits -->
            <div class="bg-white rounded-2xl shadow-lg p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-purple-100 p-3 rounded-xl mr-4">
                        <i class="fas fa-wallet text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Available Credits</p>
                        <p class="text-2xl font-bold text-purple-600" id="availableCredits">{{ auth()->user()->credit ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Layout -->
        <div class="flex flex-col xl:flex-row gap-6">
            <!-- Sidebar: Filter and Search -->
            <div class="w-full xl:w-80 flex-shrink-0 order-2 xl:order-1">
                <div class="bg-white rounded-2xl shadow-lg p-4 sticky top-8">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-filter text-indigo-600 text-xl mr-3"></i>
                        <h3 class="text-xl font-bold text-gray-800">Filter & Search</h3>
                    </div>

                    <div class="space-y-5">
                        <!-- Search Input -->
                        <div>
                            <label for="search-input" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-search mr-2"></i>Search Questions
                            </label>
                            <input type="text" id="search-input"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                   placeholder="Search by ID or content...">
                        </div>

                        <!-- Education Level Filter -->
                        <div>
                            <label for="filter-education" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-graduation-cap mr-2"></i>Education Level
                            </label>
                            <select id="filter-education" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">All Levels</option>
                                @foreach($questions->pluck('education_level')->unique()->filter() as $level)
                                    <option value="{{ $level }}">{{ $level }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Question Type Filter -->
                        <div>
                            <label for="filter-type" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-tags mr-2"></i>Question Type
                            </label>
                            <select id="filter-type" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">All Types</option>
                                @foreach($questions->pluck('question_type')->unique()->filter() as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Subject Selector (for loading topics) -->
                        <div>
                            <label for="filter-subject" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-book mr-2"></i>Select Subject
                            </label>
                            <select id="filter-subject" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Choose subject to load topics</option>
                                @foreach($questions->pluck('topic.subject.name')->unique()->filter() as $subject)
                                    <option value="{{ $subject }}">{{ $subject }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Topic Filter -->
                        <div>
                            <label for="filter-topic" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-tags mr-2"></i>Filter by Topic
                            </label>
                            <select id="filter-topic" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent" disabled>
                                <option value="">Select a subject first</option>
                            </select>
                        </div>

                        <!-- Difficulty Filter -->
                        <div>
                            <label for="filter-difficulty" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-signal mr-2"></i>Difficulty Level
                            </label>
                            <select id="filter-difficulty" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">All Difficulties</option>
                                @foreach($questions->pluck('difficulty')->unique()->filter() as $diff)
                                    <option value="{{ $diff }}">{{ ucfirst($diff) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-3 pt-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-500 mb-2">
                                    <i class="fas fa-magic mr-1"></i>
                                    Filters apply automatically
                                </p>
                            </div>
                            <button type="button" id="clearFilters"
                                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-xl font-semibold transition-all duration-200">
                                <i class="fas fa-times mr-2"></i>Clear All Filters
                            </button>
                        </div>

                        <!-- Quick Actions -->
                        <div class="border-t pt-5 mt-6">
                            <h4 class="font-semibold text-gray-700 mb-3">Quick Actions</h4>
                            <div class="space-y-2">
                                <button type="button" id="selectAll"
                                        class="w-full text-left px-3 py-2 rounded-lg text-sm text-blue-600 hover:bg-blue-50 transition-colors">
                                    <i class="fas fa-check-double mr-2"></i>Select All Visible
                                </button>
                                <button type="button" id="deselectAll"
                                        class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <i class="fas fa-times-circle mr-2"></i>Deselect All
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Question Area -->
            <div class="flex-1 order-1 xl:order-2">
                <!-- Question Content -->
                <div class="w-full">
                        <!-- Navigation Controls -->
                        <div class="bg-white rounded-2xl shadow-lg p-4 mb-4">
                            <!-- Main Navigation Row -->
                            <div class="flex justify-center items-center gap-2 mb-3">
                                <!-- First Button -->
                                <button type="button" id="firstBtn"
                                        class="bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-600 p-2 sm:p-3 rounded-full transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0"
                                        title="First Question">
                                    <i class="fas fa-backward-step text-sm sm:text-lg"></i>
                                </button>

                                <!-- Previous Button -->
                                <button type="button" id="prevBtn"
                                        class="bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-600 p-2 sm:p-3 rounded-full transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0"
                                        title="Previous Question">
                                    <i class="fas fa-caret-left text-sm sm:text-lg"></i>
                                </button>

                                <!-- Question ID Selector -->
                                <div class="flex items-center bg-gray-50 rounded-full px-3 sm:px-4 py-2 border border-gray-200 mx-2 flex-shrink-0">
                                    <label for="gotoInput" class="text-xs sm:text-sm font-medium text-gray-600 mr-1 sm:mr-2">Q:</label>
                                    <input type="number" id="gotoInput" min="1"
                                           class="w-10 sm:w-16 bg-transparent text-center text-sm sm:text-lg font-bold text-gray-800 focus:outline-none focus:ring-0 border-0 p-0"
                                           placeholder="1">
                                    <span class="text-gray-400 mx-1">/</span>
                                    <span id="totalQuestionsNav" class="text-gray-600 font-medium text-xs sm:text-sm">{{ count($questions) }}</span>
                                    <button type="button" id="gotoBtn"
                                            class="ml-1 sm:ml-2 text-blue-600 hover:text-blue-800 transition-colors"
                                            title="Go to Question">
                                        <i class="fas fa-arrow-right text-xs sm:text-sm"></i>
                                    </button>
                                </div>

                                <!-- Next Button -->
                                <button type="button" id="nextBtn"
                                        class="bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-600 p-2 sm:p-3 rounded-full transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0"
                                        title="Next Question">
                                    <i class="fas fa-caret-right text-sm sm:text-lg"></i>
                                </button>

                                <!-- Last Button -->
                                <button type="button" id="lastBtn"
                                        class="bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-600 p-2 sm:p-3 rounded-full transition-all duration-200 shadow-sm hover:shadow-md flex-shrink-0"
                                        title="Last Question">
                                    <i class="fas fa-forward-step text-sm sm:text-lg"></i>
                                </button>

                                <!-- Download Format Toggle -->
                                <div class="ml-2 sm:ml-4 flex items-center bg-white rounded-full border border-gray-300 shadow-sm flex-shrink-0">
                                    <select id="downloadFormat" 
                                            class="text-xs sm:text-sm bg-transparent border-none outline-none focus:ring-0 pr-8 pl-3 py-2 rounded-l-full text-gray-700 font-medium cursor-pointer">
                                        <option value="word">📄 Word</option>
                                        <option value="latex-folders">📂 LaTeX (Folders)</option>
                                    </select>
                                </div>

                                <!-- Download Button -->
                                <button type="button" id="downloadBtn"
                                        class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white px-3 sm:px-4 py-2 rounded-full font-semibold shadow-lg transition-all duration-200 transform hover:scale-105 disabled:transform-none text-xs sm:text-sm flex-shrink-0"
                                        disabled
                                        title="Download Selected Questions">
                                    <i class="fas fa-download mr-1 sm:mr-2"></i>
                                    <span class="hidden sm:inline">Download </span>(<span id="downloadCount">0</span>)
                                </button>

                                <!-- Send Email Button -->
                                <button type="button" id="emailBtn"
                                        class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white px-3 sm:px-4 py-2 rounded-full font-semibold shadow-lg transition-all duration-200 transform hover:scale-105 disabled:transform-none text-xs sm:text-sm flex-shrink-0"
                                        disabled
                                        title="Send Selected Questions by Email">
                                    <i class="fas fa-envelope mr-1 sm:mr-2"></i>
                                    <span class="hidden sm:inline">Email </span>(<span id="emailCount">0</span>)
                                </button>
                            </div>

                            <!-- Info Row -->
                            <div class="flex flex-col sm:flex-row justify-between items-center gap-2 text-xs sm:text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <span>Question <span id="currentQuestionIndex">1</span> of <span id="totalVisible">{{ count($questions) }}</span></span>
                                </div>
                                <div class="flex items-center gap-2 sm:gap-4">
                                    <span><span id="selectedCount">0</span> selected</span>
                                    <span><span id="creditsNeeded">0</span> credits needed</span>
                                    <span class="text-purple-600 font-medium">{{ auth()->user()->credit ?? 0 }} available</span>
                                    <!-- Credit Warning -->
                                    <div id="creditWarning" class="hidden">
                                        <span class="text-red-600 font-medium">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Need <span id="creditShortage">0</span> more
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question Cards Container -->
                        <div id="questionContainer" class="question-container">
                            <!-- Questions will be rendered here by JavaScript -->
                        </div>

                        <!-- No Questions Found -->
                        <div id="noQuestionsFound" class="hidden text-center py-12">
                            <div class="bg-white rounded-2xl shadow-lg p-8">
                                <i class="fas fa-search text-gray-400 text-6xl mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Questions Found</h3>
                                <p class="text-gray-500">Try adjusting your search criteria or filters.</p>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="flex items-center justify-center min-h-full p-4">
        <div class="relative mx-auto border-0 w-full md:w-2/3 lg:w-1/2 xl:w-2/5 max-w-2xl shadow-2xl rounded-xl bg-white">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-xl">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 p-2 rounded-lg mr-3">
                        <i class="fas fa-envelope text-white text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Send Questions by Email</h3>
                        <p class="text-blue-100 text-sm">Send selected questions to recipients</p>
                    </div>
                </div>
                <button type="button" id="closeEmailModal" class="text-white hover:text-blue-200 transition-colors p-1">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

        <!-- Modal Content -->
        <form id="emailForm" class="p-6 space-y-4">
            <!-- Recipients -->
            <div>
                <label for="emailRecipients" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-users mr-2 text-blue-600"></i>Recipient Email(s)
                </label>
                <input type="email" id="emailRecipients" name="recipients" multiple
                       class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-sm"
                       placeholder="example@email.com, another@email.com"
                       required>
                <p class="text-xs text-gray-500 mt-1">Separate multiple emails with commas</p>
            </div>

            <!-- Subject and Format in one row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Subject -->
                <div>
                    <label for="emailSubject" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-tag mr-2 text-blue-600"></i>Subject
                    </label>
                    <input type="text" id="emailSubject" name="subject"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-sm"
                           placeholder="Questions from Edufacilita"
                           value="Questions from Edufacilita"
                           required>
                </div>

                <!-- Format Selection -->
                <div>
                    <label for="emailFormat" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-file-alt mr-2 text-blue-600"></i>Format
                    </label>
                    <select id="emailFormat" name="format"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-sm">
                        <option value="word">📄 Word (ZIP)</option>
                        <option value="latex-folders">📂 LaTeX (ZIP)</option>
                    </select>
                </div>
            </div>

            <!-- Message -->
            <div>
                <label for="emailMessage" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-comment-alt mr-2 text-blue-600"></i>Message
                </label>
                <textarea id="emailMessage" name="message" rows="3"
                          class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 resize-none text-sm"
                          placeholder="Please find the attached questions..."
                          required>Please find the attached questions from Edufacilita. These questions have been carefully selected for your review.

Best regards,
Edufacilita Team</textarea>
            </div>

            <!-- Selected Questions Info -->
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        <span class="font-semibold text-blue-800 text-sm">Email Summary</span>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 text-xs">
                    <div class="text-center">
                        <div class="text-gray-600">Questions</div>
                        <div class="font-bold text-blue-800 text-lg" id="modalSelectedCount">0</div>
                    </div>
                    <div class="text-center">
                        <div class="text-gray-600">Credits</div>
                        <div class="font-bold text-blue-800 text-lg" id="modalCreditsNeeded">0</div>
                    </div>
                    <div class="text-center">
                        <div class="text-gray-600">Available</div>
                        <div class="font-bold text-purple-600 text-lg">{{ auth()->user()->credit ?? 0 }}</div>
                    </div>
                </div>
                <div id="modalCreditWarning" class="hidden mt-2 p-2 bg-red-100 border border-red-300 rounded-lg">
                    <span class="text-red-600 font-medium text-xs">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Insufficient credits! Need <span id="modalCreditShortage">0</span> more credits.
                    </span>
                </div>
            </div>

            <!-- Modal Actions -->
            <div class="flex justify-end space-x-3 pt-2">
                <button type="button" id="cancelEmailBtn"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-all duration-200 text-sm">
                    <i class="fas fa-times mr-1"></i>Cancel
                </button>
                <button type="submit" id="sendEmailBtn"
                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white rounded-lg font-medium shadow-lg transition-all duration-200 transform hover:scale-105 disabled:transform-none text-sm">
                    <i class="fas fa-paper-plane mr-1"></i>Send Email
                </button>
            </div>
        </form>
    </div>
</div>@push('scripts')
<script>
    // Questions data from Laravel
    const questionsData = @json($questions);
    let filteredQuestions = [...questionsData];
    let currentIndex = 0;
    let selectedQuestions = new Set();
    const userCredits = {{ auth()->user()->credit ?? 0 }};

    // DOM Elements
    const questionContainer = document.getElementById('questionContainer');
    const noQuestionsFound = document.getElementById('noQuestionsFound');
    const selectedCount = document.getElementById('selectedCount');
    const creditsNeeded = document.getElementById('creditsNeeded');
    const currentQuestionIndex = document.getElementById('currentQuestionIndex');
    const totalVisible = document.getElementById('totalVisible');
    const totalQuestionsNav = document.getElementById('totalQuestionsNav');
    const gotoInput = document.getElementById('gotoInput');
    const downloadBtn = document.getElementById('downloadBtn');
    const downloadCount = document.getElementById('downloadCount');
    const creditWarning = document.getElementById('creditWarning');
    const creditShortage = document.getElementById('creditShortage');
    
    // Email modal elements
    const emailBtn = document.getElementById('emailBtn');
    const emailCount = document.getElementById('emailCount');
    const emailModal = document.getElementById('emailModal');
    const emailForm = document.getElementById('emailForm');
    const closeEmailModal = document.getElementById('closeEmailModal');
    const cancelEmailBtn = document.getElementById('cancelEmailBtn');
    const sendEmailBtn = document.getElementById('sendEmailBtn');
    const modalSelectedCount = document.getElementById('modalSelectedCount');
    const modalCreditsNeeded = document.getElementById('modalCreditsNeeded');
    const modalCreditWarning = document.getElementById('modalCreditWarning');
    const modalCreditShortage = document.getElementById('modalCreditShortage');

    // Difficulty color mapping
    const difficultyColors = {
        'easy': 'bg-green-100 text-green-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'hard': 'bg-red-100 text-red-800'
    };

    // Question type color mapping
    const typeColors = {
        'multiple_choice': 'bg-blue-100 text-blue-800',
        'true_false': 'bg-purple-100 text-purple-800',
        'short_answer': 'bg-indigo-100 text-indigo-800',
        'essay': 'bg-pink-100 text-pink-800'
    };

    // Render question card
    function renderQuestionCard(question, index) {
        const isSelected = selectedQuestions.has(question.id);
        const difficultyClass = difficultyColors[question.difficulty?.toLowerCase()] || 'bg-gray-100 text-gray-800';
        const typeClass = typeColors[question.question_type?.toLowerCase()] || 'bg-gray-100 text-gray-800';

        // Display images if available
        let questionImageHtml = '';
        if (question.question_image_path) {
            questionImageHtml = `
                <div class="mt-4">
                    <img src="/storage/${question.question_image_path}" 
                         alt="Question Image" 
                         class="question-image" 
                         style="width: 350px; height: auto; max-width: 100%; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin: 10px 0; display: block;">
                </div>
            `;
        }

        let answerImageHtml = '';
        if (question.answer_image_path) {
            answerImageHtml = `
                <div class="mt-4">
                    <img src="/storage/${question.answer_image_path}" 
                         alt="Answer Image" 
                         class="answer-image" 
                         style="width: 350px; height: auto; max-width: 100%; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin: 10px 0; display: block;">
                </div>
            `;
        }

        return `
            <div class="question-card bg-white rounded-2xl shadow-lg border border-gray-100 p-6 question-card-enter no-select protected-content"
                 data-question-id="${question.id}" data-index="${index}">
                <!-- Question Header -->
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-xl mr-4">
                            <i class="fas fa-question text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Question #${question.id}</h3>
                            <p class="text-gray-600">Question with correct answer provided</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="checkbox-${question.id}"
                               class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 question-checkbox"
                               data-question-id="${question.id}" ${isSelected ? 'checked' : ''}>
                        <label for="checkbox-${question.id}" class="ml-2 text-sm font-medium text-gray-700">Select</label>
                    </div>
                </div>

                <!-- Question Metadata -->
                <div class="metadata-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                    <div class="question-metadata flex items-center space-x-2">
                        <i class="fas fa-graduation-cap text-gray-600"></i>
                        <span class="text-sm text-gray-600">Education:</span>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium badge-animate">
                            ${question.education_level || 'Not specified'}
                        </span>
                    </div>
                    <div class="question-metadata flex items-center space-x-2">
                        <i class="fas fa-tag text-gray-600"></i>
                        <span class="text-sm text-gray-600">Type:</span>
                        <span class="px-3 py-1 ${typeClass} rounded-full text-sm font-medium badge-animate">
                            ${question.question_type || 'Not specified'}
                        </span>
                    </div>
                    <div class="question-metadata flex items-center space-x-2">
                        <i class="fas fa-book text-gray-600"></i>
                        <span class="text-sm text-gray-600">Subject:</span>
                        <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-medium badge-animate">
                            ${question.topic && question.topic.subject ? question.topic.subject.name : 'Not specified'}
                        </span>
                    </div>
                    <div class="question-metadata flex items-center space-x-2">
                        <i class="fas fa-tags text-gray-600"></i>
                        <span class="text-sm text-gray-600">Topic:</span>
                        <span class="px-3 py-1 bg-teal-100 text-teal-800 rounded-full text-sm font-medium badge-animate">
                            ${question.topic ? question.topic.name : 'Not specified'}
                        </span>
                    </div>
                    <div class="question-metadata flex items-center space-x-2">
                        <i class="fas fa-signal text-gray-600"></i>
                        <span class="text-sm text-gray-600">Difficulty:</span>
                        <span class="px-3 py-1 ${difficultyClass} rounded-full text-sm font-medium badge-animate">
                            ${question.difficulty ? question.difficulty.charAt(0).toUpperCase() + question.difficulty.slice(1) : 'Not specified'}
                        </span>
                    </div>
                </div>

                <!-- Question Content -->
                <div class="space-y-6">
                    <!-- Question Text -->
                    <div class="bg-white rounded-xl question-content-section shadow-sm border border-gray-100 question-content">
                        <div class="flex items-center mb-4">
                            <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-question text-indigo-600"></i>
                            </div>
                            <label class="text-lg font-bold text-gray-800">Question</label>
                        </div>
                        <div class="prose prose-lg max-w-none">
                            <div class="text-gray-700 leading-relaxed no-select mathjax-content">
                                ${(question.question && question.question.trim()) ? question.question.replace(/\n/g, '<br>') : 'Question text not available'}
                            </div>
                            ${questionImageHtml}
                        </div>
                    </div>

                    <!-- Answer Section -->
                    <div class="bg-green-50 rounded-xl question-content-section shadow-sm border border-green-200 question-content">
                        <div class="flex items-center mb-4">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <label class="text-lg font-bold text-green-800">Correct Answer</label>
                        </div>
                        <div class="prose prose-lg max-w-none">
                            <div class="text-green-700 leading-relaxed no-select bg-white p-4 rounded-lg border border-green-200 mathjax-content">
                                ${(question.answer && question.answer.trim()) ? question.answer.replace(/\n/g, '<br>') : 'Answer text not available'}
                            </div>
                            ${answerImageHtml}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Update display
    function updateDisplay() {
        questionContainer.innerHTML = '';

        if (filteredQuestions.length === 0) {
            noQuestionsFound.classList.remove('hidden');
            questionContainer.classList.add('hidden');
            return;
        }

        noQuestionsFound.classList.add('hidden');
        questionContainer.classList.remove('hidden');

        if (currentIndex >= filteredQuestions.length) {
            currentIndex = 0;
        }

        const question = filteredQuestions[currentIndex];
        questionContainer.innerHTML = renderQuestionCard(question, currentIndex);

        // Update navigation info
        currentQuestionIndex.textContent = currentIndex + 1;
        totalVisible.textContent = filteredQuestions.length;
        totalQuestionsNav.textContent = filteredQuestions.length;
        gotoInput.value = currentIndex + 1;
        gotoInput.max = filteredQuestions.length;

        // Add event listener to checkbox
        const checkbox = document.querySelector('.question-checkbox');
        if (checkbox) {
            checkbox.addEventListener('change', handleCheckboxChange);
        }

        // Add protection to dynamically added images
        const images = questionContainer.querySelectorAll('img');
        images.forEach(img => {
            img.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            img.addEventListener('dragstart', function(e) {
                e.preventDefault();
                return false;
            });
            img.addEventListener('selectstart', function(e) {
                e.preventDefault();
                return false;
            });
            // Disable image saving shortcuts
            img.addEventListener('keydown', function(e) {
                if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // Process MathJax content
        if (window.MathJax && window.MathJax.typesetPromise) {
            window.MathJax.typesetPromise([questionContainer]).catch((err) => {
                console.log('MathJax typeset error:', err);
            });
        }
    }

    // Handle checkbox change
    function handleCheckboxChange(e) {
        const questionId = parseInt(e.target.dataset.questionId);

        if (e.target.checked) {
            selectedQuestions.add(questionId);
        } else {
            selectedQuestions.delete(questionId);
        }

        updateSelectionUI();
    }

    // Handle download
    function handleDownload(e) {
        if (selectedQuestions.size === 0) {
            alert('Please select at least one question to download.');
            return;
        }

        const totalCredits = selectedQuestions.size;
        if (userCredits < totalCredits) {
            alert(`Insufficient credits! You need ${totalCredits} credits but only have ${userCredits}.`);
            return;
        }

        const downloadFormat = document.getElementById('downloadFormat').value;
        let formatName = 'Word';
        if (downloadFormat === 'latex') {
            formatName = 'LaTeX (Single)';
        } else if (downloadFormat === 'latex-folders') {
            formatName = 'LaTeX (Folders)';
        }

        if (confirm(`Download ${selectedQuestions.size} selected questions as ${formatName}? (${totalCredits} credits will be deducted)`)) {
            // Create and submit a form dynamically
            const form = document.createElement('form');
            form.method = 'POST';
            
            // Set the correct route based on format
            if (downloadFormat === 'latex-folders') {
                form.action = '{{ route("user.questions.download-latex-zip") }}';
            } else {
                form.action = '{{ route("user.questions.download") }}';
            }
            
            // Add CSRF token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Add selected questions
            const questionsInput = document.createElement('input');
            questionsInput.type = 'hidden';
            questionsInput.name = 'selected_questions';
            questionsInput.value = JSON.stringify([...selectedQuestions]);
            form.appendChild(questionsInput);
            
            // Add download format (only for non-latex-folders)
            if (downloadFormat !== 'latex-folders') {
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = downloadFormat;
                form.appendChild(formatInput);
            }
            
            document.body.appendChild(form);
            form.submit();

            // Show loading message
            e.target.disabled = true;
            e.target.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

            // Refresh page after 5 seconds
            setTimeout(function() {
                window.location.reload();
            }, 5000);
        }
    }

    // Show email modal
    function showEmailModal() {
        if (selectedQuestions.size === 0) {
            alert('Please select at least one question to send by email.');
            return;
        }

        const totalCredits = selectedQuestions.size;
        if (userCredits < totalCredits) {
            alert(`Insufficient credits! You need ${totalCredits} credits but only have ${userCredits}.`);
            return;
        }

        // Update modal information
        if (modalSelectedCount) modalSelectedCount.textContent = selectedQuestions.size;
        if (modalCreditsNeeded) modalCreditsNeeded.textContent = totalCredits;

        // Show/hide credit warning in modal
        if (modalCreditWarning && modalCreditShortage) {
            if (totalCredits > userCredits) {
                modalCreditWarning.classList.remove('hidden');
                modalCreditShortage.textContent = totalCredits - userCredits;
                sendEmailBtn.disabled = true;
            } else {
                modalCreditWarning.classList.add('hidden');
                sendEmailBtn.disabled = false;
            }
        }

        // Set the email format to match the download format
        const downloadFormat = document.getElementById('downloadFormat').value;
        const emailFormat = document.getElementById('emailFormat');
        if (emailFormat) {
            emailFormat.value = downloadFormat;
        }

        // Show modal
        emailModal.classList.remove('hidden');
        emailModal.style.display = 'flex';
        emailModal.style.alignItems = 'center';
        emailModal.style.justifyContent = 'center';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    // Hide email modal
    function hideEmailModal() {
        emailModal.classList.add('hidden');
        emailModal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
        
        // Reset form
        if (emailForm) {
            emailForm.reset();
            // Restore default values
            document.getElementById('emailSubject').value = 'Questions from Edufacilita';
            document.getElementById('emailMessage').value = `Please find the attached questions from Edufacilita. These questions have been carefully selected for your review.

Best regards,
Edufacilita Team`;
        }
    }

    // Handle email sending
    function handleEmailSend(e) {
        e.preventDefault();

        if (selectedQuestions.size === 0) {
            alert('Please select at least one question to send by email.');
            return;
        }

        const totalCredits = selectedQuestions.size;
        if (userCredits < totalCredits) {
            alert(`Insufficient credits! You need ${totalCredits} credits but only have ${userCredits}.`);
            return;
        }

        // Get form data
        const formData = new FormData(emailForm);
        const recipients = formData.get('recipients').trim();
        const subject = formData.get('subject').trim();
        const message = formData.get('message').trim();
        const format = formData.get('format');

        // Validate required fields
        if (!recipients || !subject || !message) {
            alert('Please fill in all required fields.');
            return;
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const emails = recipients.split(',').map(email => email.trim());
        const invalidEmails = emails.filter(email => email && !emailRegex.test(email));
        
        if (invalidEmails.length > 0) {
            alert(`Invalid email format: ${invalidEmails.join(', ')}`);
            return;
        }

        let formatName = 'Word';
        if (format === 'latex-folders') {
            formatName = 'LaTeX (Folders)';
        }

        if (confirm(`Send ${selectedQuestions.size} selected questions as ${formatName} to ${emails.length} recipient(s)? (${totalCredits} credits will be deducted)`)) {
            // Disable send button and show loading
            sendEmailBtn.disabled = true;
            sendEmailBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';

            // Prepare data for submission
            const submitData = {
                _token: '{{ csrf_token() }}',
                selected_questions: JSON.stringify([...selectedQuestions]),
                recipients: recipients,
                subject: subject,
                message: message,
                format: format
            };

            // Send email request
            fetch('{{ route("user.questions.send-email") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(submitData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Email sent successfully to ${emails.length} recipient(s)!`);
                    hideEmailModal();
                    // Refresh page to update credits
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(data.message || 'Failed to send email. Please try again.');
                    sendEmailBtn.disabled = false;
                    sendEmailBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Email';
                }
            })
            .catch(error => {
                console.error('Email send error:', error);
                alert('Failed to send email. Please check your connection and try again.');
                sendEmailBtn.disabled = false;
                sendEmailBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Email';
            });
        }
    }

    // Update selection UI
    function updateSelectionUI() {
        const count = selectedQuestions.size;
        const credits = count;

        // Update stats dashboard
        selectedCount.textContent = count;
        creditsNeeded.textContent = credits;

        // Update download button
        if (downloadBtn) {
            downloadBtn.disabled = count === 0 || credits > userCredits;
        }
        
        // Update download count
        if (downloadCount) {
            downloadCount.textContent = count;
        }

        // Update email button
        if (emailBtn) {
            emailBtn.disabled = count === 0 || credits > userCredits;
        }
        
        // Update email count
        if (emailCount) {
            emailCount.textContent = count;
        }

        // Show/hide credit warning
        if (creditWarning && creditShortage) {
            if (credits > userCredits && count > 0) {
                creditWarning.classList.remove('hidden');
                creditShortage.textContent = credits - userCredits;
            } else {
                creditWarning.classList.add('hidden');
            }
        }
    }

    // Navigation functions
    function goToQuestion(index) {
        if (index >= 0 && index < filteredQuestions.length) {
            currentIndex = index;
            updateDisplay();
        }
    }

    // Filter questions
    function filterQuestions() {
        const searchTerm = document.getElementById('search-input').value.toLowerCase();
        const educationFilter = document.getElementById('filter-education').value;
        const typeFilter = document.getElementById('filter-type').value;
        const topicFilter = document.getElementById('filter-topic').value;
        const difficultyFilter = document.getElementById('filter-difficulty').value;

        filteredQuestions = questionsData.filter(question => {
            const matchesSearch = !searchTerm ||
                question.id.toString().includes(searchTerm) ||
                (question.question && question.question.toLowerCase().includes(searchTerm));

            const matchesEducation = !educationFilter || question.education_level === educationFilter;
            const matchesType = !typeFilter || question.question_type === typeFilter;
            const matchesTopic = !topicFilter || (question.topic && question.topic.name === topicFilter);
            const matchesDifficulty = !difficultyFilter || question.difficulty === difficultyFilter;

            return matchesSearch && matchesEducation && matchesType && matchesTopic && matchesDifficulty;
        });

        currentIndex = 0;
        updateDisplay();
    }

    // Event listeners
    document.getElementById('clearFilters').addEventListener('click', () => {
        document.getElementById('search-input').value = '';
        document.getElementById('filter-education').value = '';
        document.getElementById('filter-type').value = '';
        document.getElementById('filter-subject').value = '';
        document.getElementById('filter-topic').value = '';
        document.getElementById('filter-topic').disabled = true;
        document.getElementById('filter-topic').innerHTML = '<option value="">Select a subject first</option>';
        document.getElementById('filter-difficulty').value = '';
        filterQuestions();
    });

    // Add real-time filtering on all filter inputs
    document.getElementById('filter-education').addEventListener('change', filterQuestions);
    document.getElementById('filter-type').addEventListener('change', filterQuestions);
    document.getElementById('filter-difficulty').addEventListener('change', filterQuestions);

    // Subject/Topic dynamic loading
    document.getElementById('filter-subject').addEventListener('change', function() {
        const subjectName = this.value;
        const topicSelect = document.getElementById('filter-topic');

        // Reset topic select
        topicSelect.innerHTML = '<option value="">Loading topics...</option>';
        topicSelect.disabled = true;

        if (subjectName) {
            // Find the subject ID from the questionsData
            const subjectQuestion = questionsData.find(q => q.topic && q.topic.subject && q.topic.subject.name === subjectName);

            if (subjectQuestion && subjectQuestion.topic && subjectQuestion.topic.subject) {
                const subjectId = subjectQuestion.topic.subject.id;

                // Fetch topics for selected subject
                fetch(`{{ url('subjects') }}/${subjectId}/topics`)
                    .then(response => response.json())
                    .then(topics => {
                        topicSelect.innerHTML = '<option value="">All Topics</option>';

                        topics.forEach(topic => {
                            const option = document.createElement('option');
                            option.value = topic.name;
                            option.textContent = topic.name;
                            topicSelect.appendChild(option);
                        });

                        topicSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error fetching topics:', error);
                        topicSelect.innerHTML = '<option value="">Error loading topics</option>';
                        topicSelect.disabled = false;
                    });
            } else {
                topicSelect.innerHTML = '<option value="">No topics found</option>';
                topicSelect.disabled = false;
            }
        } else {
            topicSelect.innerHTML = '<option value="">Select a subject first</option>';
            topicSelect.disabled = true;
        }

        // Clear topic filter when subject changes (but don't filter yet)
        topicSelect.value = '';
    });

    // Topic filter change event
    document.getElementById('filter-topic').addEventListener('change', filterQuestions);

    document.getElementById('selectAll').addEventListener('click', () => {
        filteredQuestions.forEach(question => selectedQuestions.add(question.id));
        updateDisplay();
        updateSelectionUI();
    });

    document.getElementById('deselectAll').addEventListener('click', () => {
        selectedQuestions.clear();
        updateDisplay();
        updateSelectionUI();
    });

    // Navigation event listeners
    document.getElementById('firstBtn').addEventListener('click', () => goToQuestion(0));
    document.getElementById('lastBtn').addEventListener('click', () => goToQuestion(filteredQuestions.length - 1));
    document.getElementById('prevBtn').addEventListener('click', () => goToQuestion(currentIndex - 1));
    document.getElementById('nextBtn').addEventListener('click', () => goToQuestion(currentIndex + 1));

    // Download button event listener
    if (downloadBtn) {
        downloadBtn.addEventListener('click', handleDownload);
    }

    // Email button event listener
    if (emailBtn) {
        emailBtn.addEventListener('click', showEmailModal);
    }

    // Email modal event listeners
    if (closeEmailModal) {
        closeEmailModal.addEventListener('click', hideEmailModal);
    }

    if (cancelEmailBtn) {
        cancelEmailBtn.addEventListener('click', hideEmailModal);
    }

    if (emailForm) {
        emailForm.addEventListener('submit', handleEmailSend);
    }

    // Close modal when clicking outside
    if (emailModal) {
        emailModal.addEventListener('click', function(e) {
            if (e.target === emailModal) {
                hideEmailModal();
            }
        });
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !emailModal.classList.contains('hidden')) {
            hideEmailModal();
        }
    });

    document.getElementById('gotoBtn').addEventListener('click', () => {
        const value = parseInt(gotoInput.value);
        if (value >= 1 && value <= filteredQuestions.length) {
            goToQuestion(value - 1);
        }
    });

    gotoInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            document.getElementById('gotoBtn').click();
        }
    });

    // Search on input (real-time filtering)
    document.getElementById('search-input').addEventListener('input', filterQuestions);

    // Initialize
    updateDisplay();
    updateSelectionUI();

    // Prevent copying and content protection
    function preventCopying() {
        // Disable right-click context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });

        // Disable common keyboard shortcuts for copying/selecting
        document.addEventListener('keydown', function(e) {
            // Disable Ctrl+A (Select All)
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+C (Copy)
            if (e.ctrlKey && e.key === 'c') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+X (Cut)
            if (e.ctrlKey && e.key === 'x') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+V (Paste) - though not needed for copying, good security practice
            if (e.ctrlKey && e.key === 'v') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+S (Save)
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+P (Print)
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                return false;
            }
            
            // Disable F12 (Developer Tools)
            if (e.key === 'F12') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+Shift+I (Developer Tools)
            if (e.ctrlKey && e.shiftKey && e.key === 'I') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+Shift+C (Element Selector)
            if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+U (View Source)
            if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                return false;
            }
        });

        // Disable text selection on mouse events
        document.addEventListener('selectstart', function(e) {
            if (e.target.closest('.question-content') || e.target.closest('.no-select')) {
                e.preventDefault();
                return false;
            }
        });

        // Disable drag and drop
        document.addEventListener('dragstart', function(e) {
            if (e.target.closest('.question-content')) {
                e.preventDefault();
                return false;
            }
        });

        // Clear selection if any exists
        document.addEventListener('mouseup', function() {
            if (window.getSelection) {
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    const container = range.commonAncestorContainer;
                    if (container.nodeType === Node.TEXT_NODE) {
                        container = container.parentNode;
                    }
                    if (container.closest && container.closest('.question-content')) {
                        selection.removeAllRanges();
                    }
                }
            }
        });

        // Prevent print screen and other screenshot methods
        document.addEventListener('keyup', function(e) {
            if (e.key === 'PrintScreen') {
                navigator.clipboard.writeText('');
                alert('Screenshots are not allowed for question content protection.');
            }
        });

        // Clear clipboard periodically to prevent copied content from being accessible
        setInterval(function() {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText('').catch(function() {
                    // Ignore errors if clipboard API is not available
                });
            }
        }, 1000);

        // Disable image saving
        document.addEventListener('dragstart', function(e) {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
                return false;
            }
        });

        // Add blur effect when dev tools might be open
        let devtools = {open: false};
        setInterval(function() {
            if (window.outerHeight - window.innerHeight > 200 || window.outerWidth - window.innerWidth > 200) {
                if (!devtools.open) {
                    devtools.open = true;
                    document.body.style.filter = 'blur(5px)';
                    setTimeout(function() {
                        alert('Developer tools detected. Please close them to continue viewing content.');
                        location.reload();
                    }, 500);
                }
            } else {
                devtools.open = false;
                document.body.style.filter = 'none';
            }
        }, 500);

        // Disable common developer shortcuts
        document.addEventListener('keydown', function(e) {
            // Disable Ctrl+Shift+K (Console)
            if (e.ctrlKey && e.shiftKey && e.key === 'K') {
                e.preventDefault();
                return false;
            }
            
            // Disable Ctrl+Shift+J (Console)
            if (e.ctrlKey && e.shiftKey && e.key === 'J') {
                e.preventDefault();
                return false;
            }
        });
    }

    // Initialize copy protection
    preventCopying();

    // Additional protection - disable console commands
    (function() {
        try {
            const devtools = /./;
            devtools.toString = function() {
                this.opened = true;
            };
            
            const loop = setInterval(function() {
                if (devtools.opened) {
                    clearInterval(loop);
                    document.body.innerHTML = '<div style="text-align: center; padding: 50px; font-size: 24px; color: red;">Developer tools detected. Access denied for security reasons.</div>';
                }
            }, 100);
        } catch (e) {
            // Ignore errors
        }
    })();
</script>
@endpush

@endsection

