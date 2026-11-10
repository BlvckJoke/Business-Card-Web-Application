
class BusinessCardsManager {
    constructor() {
        this.cards = JSON.parse(localStorage.getItem('businessCards')) || [];
        this.init();
    }

    init() {
        // Set today's date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date').value = today;
        
        // Load saved cards
        this.displayCards();
        this.updateStats();
        
        // Setup event listeners
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Form submission
        document.getElementById('cardForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.addCard();
        });

        // Export button
        document.getElementById('exportBtn')?.addEventListener('click', () => this.exportToCSV());
        
        // Clear all button
        document.getElementById('clearBtn')?.addEventListener('click', () => this.clearAllCards());
    }

    addCard() {
        const company = document.getElementById('company').value.trim();
        const category = document.getElementById('category').value;
        const country = document.getElementById('country').value.trim();
        const date = document.getElementById('date').value;
        const note = document.getElementById('note').value.trim();

        if (!company || !category || !country) {
            this.showAlert('Please fill in all required fields', 'error');
            return;
        }

        const card = {
            id: Date.now(),
            company,
            category,
            country,
            date,
            note,
            addedAt: new Date().toISOString()
        };

        this.cards.unshift(card); // Add to beginning
        this.saveToLocalStorage();
        this.displayCards();
        this.updateStats();
        this.resetForm();
        
        this.showAlert('Business card added successfully!', 'success');
    }

    deleteCard(id) {
        if (confirm('Are you sure you want to delete this card?')) {
            this.cards = this.cards.filter(card => card.id !== id);
            this.saveToLocalStorage();
            this.displayCards();
            this.updateStats();
            this.showAlert('Card deleted successfully', 'info');
        }
    }

    clearAllCards() {
        if (this.cards.length === 0) return;
        
        if (confirm('Are you sure you want to delete ALL cards? This cannot be undone.')) {
            this.cards = [];
            this.saveToLocalStorage();
            this.displayCards();
            this.updateStats();
            this.showAlert('All cards have been cleared', 'info');
        }
    }

    displayCards() {
        const cardList = document.getElementById('cardList');
        const emptyState = document.getElementById('emptyState');
        const actionButtons = document.getElementById('actionButtons');

        if (this.cards.length === 0) {
            cardList.innerHTML = '';
            emptyState.classList.remove('hidden');
            actionButtons.classList.add('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        actionButtons.classList.remove('hidden');
        
        cardList.innerHTML = this.cards.map(card => `
            <div class="bg-gradient-to-br from-white to-gray-50 border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md transition">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">${card.company}</h3>
                        <div class="flex items-center mt-1">
                            <span class="inline-block px-3 py-1 text-xs font-medium rounded-full 
                                ${this.getCategoryColor(card.category)}">
                                ${card.category}
                            </span>
                            <span class="ml-3 text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt mr-1"></i>${card.country}
                            </span>
                        </div>
                    </div>
                    <button onclick="cardsManager.deleteCard(${card.id})" 
                        class="text-gray-400 hover:text-red-500 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                    <div>
                        <div class="text-gray-500">Date Added</div>
                        <div class="font-medium">${card.date}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">ID</div>
                        <div class="font-mono text-xs">#${card.id.toString().slice(-6)}</div>
                    </div>
                </div>
                
                ${card.note ? `
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="text-gray-500 text-sm mb-1">Note:</div>
                        <div class="text-gray-700 bg-blue-50 p-3 rounded-lg">${card.note}</div>
                    </div>
                ` : ''}
            </div>
        `).join('');
    }

    updateStats() {
        // Update counts
        document.getElementById('totalCards').textContent = this.cards.length;
        document.getElementById('cardsCount').textContent = `${this.cards.length} card${this.cards.length !== 1 ? 's' : ''}`;
        
        // Count unique countries
        const countries = new Set(this.cards.map(card => card.country));
        document.getElementById('countriesCount').textContent = countries.size;
        
        // Count unique categories
        const categories = new Set(this.cards.map(card => card.category));
        document.getElementById('categoriesCount').textContent = categories.size;
        
        // Update last updated time
        document.getElementById('lastUpdated').textContent = 
            this.cards.length > 0 ? 
            `Last updated: ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}` : 
            '';
    }

    resetForm() {
        document.getElementById('cardForm').reset();
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date').value = today;
        document.getElementById('company').focus();
    }

    saveToLocalStorage() {
        localStorage.setItem('businessCards', JSON.stringify(this.cards));
    }

    exportToCSV() {
        if (this.cards.length === 0) {
            this.showAlert('No cards to export', 'error');
            return;
        }

        const headers = ['Company', 'Category', 'Country', 'Date', 'Note', 'Added At'];
        const rows = this.cards.map(card => [
            card.company,
            card.category,
            card.country,
            card.date,
            card.note,
            new Date(card.addedAt).toLocaleString()
        ]);

        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += headers.join(',') + "\n";
        rows.forEach(row => {
            csvContent += row.map(cell => `"${cell}"`).join(',') + "\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `business_cards_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        this.showAlert('CSV file downloaded successfully!', 'success');
    }

    getCategoryColor(category) {
        const colors = {
            'Technology': 'bg-blue-100 text-blue-800',
            'Finance': 'bg-green-100 text-green-800',
            'Healthcare': 'bg-red-100 text-red-800',
            'Retail': 'bg-yellow-100 text-yellow-800',
            'Education': 'bg-purple-100 text-purple-800',
            'Manufacturing': 'bg-indigo-100 text-indigo-800',
            'Hospitality': 'bg-pink-100 text-pink-800',
            'Other': 'bg-gray-100 text-gray-800'
        };
        return colors[category] || colors['Other'];
    }

    showAlert(message, type) {
        // Create alert element
        const alertEl = document.createElement('div');
        const colors = {
            success: 'bg-green-50 border-green-200 text-green-700',
            error: 'bg-red-50 border-red-200 text-red-700',
            info: 'bg-blue-50 border-blue-200 text-blue-700'
        };
        
        alertEl.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg border shadow-lg transition-transform transform translate-x-full ${colors[type]}`;
        alertEl.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-3"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(alertEl);
        
        // Animate in
        setTimeout(() => {
            alertEl.classList.remove('translate-x-full');
        }, 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            alertEl.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(alertEl);
            }, 300);
        }, 3000);
    }
}

// Initialize the app
const cardsManager = new BusinessCardsManager();

// Make manager globally available for delete buttons
window.cardsManager = cardsManager;
