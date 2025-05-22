/**
 * AGTool Finance - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Inizializza i componenti
    initTransactionForm();
    initSavingsSimulator();
    initFinancialAdvisor();
    initCharts();

    // Gestisci i messaggi flash
    handleFlashMessages();

    // Controlla notifiche automatiche
    checkAutoNotifications();
    setInterval(updateNotificationBadge, 60000);
    // Marca notifiche come lette al click sulla campanella
    var bell = document.querySelector('.fa-bell');
    if (bell) {
        bell.addEventListener('click', function() {
            fetch('notifications.php?ajax=read', { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    updateNotificationBadge();
                });
        });
    }
});

function updateNotificationBadge() {
    fetch('notifications.php?ajax=1', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var badge = document.querySelector('.navbar-badge');
            if (badge) {
                if (data.unread_count && data.unread_count > 0) {
                    badge.textContent = data.unread_count;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(function(){});
}

/**
 * Controlla notifiche automatiche (limiti, obiettivi, ricorrenze)
 */
function checkAutoNotifications() {
    fetch('check_notifications.php', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'ok' && data.notifiche && data.notifiche.length > 0) {
                data.notifiche.forEach(function(msg) { showToast('info', msg); });
            }
        })
        .catch(function() {});
}

/**
 * Inizializza il form delle transazioni
 */
function initTransactionForm() {
    const typeSelect = document.getElementById('transaction-type');
    const categorySelect = document.getElementById('transaction-category');
    const transactionForm = document.getElementById('transaction-form');

    // Carica le categorie quando cambia il tipo di transazione
    if (typeSelect && categorySelect) {
        loadCategories(typeSelect.value);
        
        typeSelect.addEventListener('change', function() {
            loadCategories(this.value);
        });
    }

    // Gestisci il submit del form
    if (transactionForm) {
        transactionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('process_transaction.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);
                    $('#addTransactionModal').modal('hide');
                    transactionForm.reset();
                    refreshTransactionsTable();
                } else {
                    showAlert('danger', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Si è verificato un errore durante l\'elaborazione della richiesta');
            });
            return false;
        });
    }
}

// Aggiorna la tabella delle transazioni senza ricaricare la pagina
function refreshTransactionsTable() {
    const tbody = document.getElementById('transactions-tbody');
    if (!tbody) {
        // Se non esiste il tbody con id, ricarica la pagina come fallback
        window.location.reload();
        return;
    }
    fetch('transactions.php?ajax=1')
        .then(response => response.text())
        .then(html => {
            tbody.innerHTML = html;
        })
        .catch(() => window.location.reload());
}

/**
 * Carica le categorie in base al tipo di transazione
 */
function loadCategories(type) {
    const categorySelect = document.getElementById('transaction-category');
    
    if (!categorySelect) return;
    
    // Resetta le opzioni
    categorySelect.innerHTML = '<option value="">Caricamento...</option>';
    
    // Carica le categorie dal server
    fetch(`get_categories.php?type=${type}`)
    .then(response => response.json())
    .then(data => {
        categorySelect.innerHTML = '';
        
        if (data.length === 0) {
            const option = document.createElement('option');
            option.value = 'Altro';
            option.textContent = 'Altro';
            categorySelect.appendChild(option);
        } else {
            data.forEach(category => {
                const option = document.createElement('option');
                option.value = category.name;
                option.textContent = category.name;
                categorySelect.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Error loading categories:', error);
        categorySelect.innerHTML = '<option value="Altro">Altro</option>';
    });
}

/**
 * Inizializza il simulatore di risparmi
 */
function initSavingsSimulator() {
    const calculateBtn = document.getElementById('calculate-savings');
    const savingAmount = document.getElementById('saving-amount');
    const savingFrequency = document.getElementById('saving-frequency');
    const savingPeriod = document.getElementById('saving-period');
    const periodValue = document.getElementById('period-value');
    const savingsResult = document.getElementById('savings-result');
    
    if (calculateBtn && savingAmount && savingFrequency && savingPeriod) {
        // Aggiorna il valore visualizzato quando si sposta lo slider
        savingPeriod.addEventListener('input', function() {
            periodValue.textContent = this.value;
        });
        
        // Calcola i risultati quando si clicca sul pulsante
        calculateBtn.addEventListener('click', function() {
            const amount = parseFloat(savingAmount.value);
            const frequency = savingFrequency.value;
            const period = parseInt(savingPeriod.value);
            
            if (isNaN(amount) || amount <= 0) {
                showAlert('warning', 'Inserisci un importo valido');
                return;
            }
            
            // Calcola il risparmio totale in base alla frequenza
            let total = 0;
            let frequencyText = '';
            
            switch (frequency) {
                case 'daily':
                    total = amount * 30 * period;
                    frequencyText = 'al giorno';
                    break;
                case 'weekly':
                    total = amount * 4 * period;
                    frequencyText = 'a settimana';
                    break;
                case 'monthly':
                    total = amount * period;
                    frequencyText = 'al mese';
                    break;
            }
            
            // Mostra i risultati
            document.getElementById('result-amount').textContent = amount;
            document.getElementById('result-frequency').textContent = frequencyText;
            document.getElementById('result-period').textContent = period;
            document.getElementById('result-total').textContent = formatCurrency(total);
            
            savingsResult.style.display = 'block';
        });
    }
    
    // Simulatore nella pagina advisor.php
    const simAmount = document.getElementById('sim-amount');
    const simFrequency = document.getElementById('sim-frequency');
    const simPeriod = document.getElementById('sim-period');
    const simPeriodDisplay = document.getElementById('sim-period-display');
    const simCalculate = document.getElementById('sim-calculate');
    const simResults = document.getElementById('sim-results');
    const simResultsMessage = document.getElementById('sim-results-message');
    const simResultsTotal = document.getElementById('sim-results-total');
    
    if (simPeriod && simPeriodDisplay) {
        simPeriod.addEventListener('input', function() {
            simPeriodDisplay.textContent = this.value + ' mesi';
        });
    }
    
    if (simCalculate && simAmount && simFrequency && simPeriod) {
        simCalculate.addEventListener('click', function() {
            const amount = parseFloat(simAmount.value);
            const frequency = simFrequency.value;
            const period = parseInt(simPeriod.value);
            
            if (isNaN(amount) || amount <= 0) {
                showAlert('warning', 'Inserisci un importo valido');
                return;
            }
            
            // Ottieni il calcolo dal server (include anche la logica per i consigli)
            fetch(`advisor.php?action=calculate_savings&amount=${amount}&frequency=${frequency}&period=${period}`)
            .then(response => response.json())
            .then(data => {
                simResultsMessage.textContent = data.message;
                simResultsTotal.textContent = data.formatted_total;
                simResults.style.display = 'block';
                
                // Imposta i valori per la creazione dell'obiettivo
                const createGoalBtn = document.getElementById('sim-create-goal');
                if (createGoalBtn) {
                    createGoalBtn.onclick = function() {
                        // Apri il modale dell'obiettivo e precompila i campi
                        $('#addGoalModal').modal('show');
                        document.getElementById('goal-name').value = 'Obiettivo Risparmio';
                        document.getElementById('goal-amount').value = data.total;
                        
                        // Calcola la data target (data attuale + periodo in mesi)
                        const targetDate = new Date();
                        targetDate.setMonth(targetDate.getMonth() + period);
                        
                        // Formatta la data nel formato yyyy-mm-dd
                        const year = targetDate.getFullYear();
                        const month = String(targetDate.getMonth() + 1).padStart(2, '0');
                        const day = String(targetDate.getDate()).padStart(2, '0');
                        document.getElementById('goal-date').value = `${year}-${month}-${day}`;
                    };
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Si è verificato un errore durante il calcolo');
            });
        });
    }
}

/**
 * Inizializza il consigliere finanziario
 */
function initFinancialAdvisor() {
    const askAdvisor = document.getElementById('ask-advisor');
    const advisorQuestion = document.getElementById('advisor-question');
    const advisorMessages = document.getElementById('advisor-messages');
    
    if (askAdvisor && advisorQuestion && advisorMessages) {
        askAdvisor.addEventListener('click', askFinancialQuestion);
        
        advisorQuestion.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                askFinancialQuestion();
            }
        });
    }
    
    // Per la pagina advisor.php
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');
    
    if (chatForm && chatInput && chatMessages) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const question = chatInput.value.trim();
            if (question === '') return;
            
            // Aggiungi il messaggio dell'utente alla chat
            addChatMessage(question, 'user');
            
            // Resetta l'input
            chatInput.value = '';
            
            // Ottieni la risposta dal consigliere
            fetch(`advisor.php?action=get_advice&question=${encodeURIComponent(question)}`)
            .then(response => response.json())
            .then(data => {
                // Aggiungi la risposta del consigliere alla chat
                const message = `<strong>${data.title}</strong><br>${data.message}`;
                addChatMessage(message, 'advisor');
                
                // Scorri verso il basso per mostrare il nuovo messaggio
                chatMessages.scrollTop = chatMessages.scrollHeight;
            })
            .catch(error => {
                console.error('Error:', error);
                addChatMessage('Mi dispiace, si è verificato un errore durante l\'elaborazione della richiesta.', 'advisor');
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
        });
    }
}

/**
 * Funzione per aggiungere un messaggio alla chat
 */
function addChatMessage(message, sender) {
    const chatMessages = document.getElementById('chat-messages');
    if (!chatMessages) return;
    
    const now = new Date();
    const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    
    let html = '';
    
    if (sender === 'user') {
        html = `
            <div class="direct-chat-msg right">
                <div class="direct-chat-infos clearfix">
                    <span class="direct-chat-name float-right">Tu</span>
                    <span class="direct-chat-timestamp float-left">${time}</span>
                </div>
                <div class="direct-chat-img" style="background-color: #007bff; color: white; text-align: center; line-height: 40px;">
                    <i class="fas fa-user"></i>
                </div>
                <div class="direct-chat-text">
                    ${message}
                </div>
            </div>
        `;
    } else {
        html = `
            <div class="direct-chat-msg">
                <div class="direct-chat-infos clearfix">
                    <span class="direct-chat-name float-left">Consulente</span>
                    <span class="direct-chat-timestamp float-right">${time}</span>
                </div>
                <div class="direct-chat-img" style="background-color: #28a745; color: white; text-align: center; line-height: 40px;">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="direct-chat-text">
                    ${message}
                </div>
            </div>
        `;
    }
    
    chatMessages.innerHTML += html;
}

/**
 * Funzione per chiedere un consiglio al consulente finanziario
 */
function askFinancialQuestion() {
    const advisorQuestion = document.getElementById('advisor-question');
    const advisorMessages = document.getElementById('advisor-messages');
    
    if (!advisorQuestion || !advisorMessages) return;
    
    const question = advisorQuestion.value.trim();
    if (question === '') return;
    
    // Aggiungi il messaggio dell'utente
    const userMessage = document.createElement('div');
    userMessage.className = 'direct-chat-msg right';
    userMessage.innerHTML = `
        <div class="direct-chat-infos clearfix">
            <span class="direct-chat-name float-right">Tu</span>
        </div>
        <div class="direct-chat-img bg-primary rounded-circle d-flex justify-content-center align-items-center">
            <i class="fas fa-user"></i>
        </div>
        <div class="direct-chat-text">
            ${question}
        </div>
    `;
    advisorMessages.appendChild(userMessage);
    
    // Resetta l'input
    advisorQuestion.value = '';
    
    // Ottieni la risposta dal consigliere
    fetch(`advisor.php?action=get_advice&question=${encodeURIComponent(question)}`)
    .then(response => response.json())
    .then(data => {
        // Aggiungi la risposta del consigliere
        const advisorMessage = document.createElement('div');
        advisorMessage.className = 'direct-chat-msg';
        advisorMessage.innerHTML = `
            <div class="direct-chat-infos clearfix">
                <span class="direct-chat-name float-left">Consulente</span>
            </div>
            <div class="direct-chat-img bg-info rounded-circle d-flex justify-content-center align-items-center">
                <i class="fas fa-robot"></i>
            </div>
            <div class="direct-chat-text">
                <strong>${data.title}</strong><br>
                ${data.message}
            </div>
        `;
        advisorMessages.appendChild(advisorMessage);
        
        // Scorri verso il basso per mostrare il nuovo messaggio
        advisorMessages.scrollTop = advisorMessages.scrollHeight;
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Aggiungi un messaggio di errore
        const errorMessage = document.createElement('div');
        errorMessage.className = 'direct-chat-msg';
        errorMessage.innerHTML = `
            <div class="direct-chat-infos clearfix">
                <span class="direct-chat-name float-left">Consulente</span>
            </div>
            <div class="direct-chat-img bg-info rounded-circle d-flex justify-content-center align-items-center">
                <i class="fas fa-robot"></i>
            </div>
            <div class="direct-chat-text">
                Mi dispiace, si è verificato un errore durante l'elaborazione della richiesta.
            </div>
        `;
        advisorMessages.appendChild(errorMessage);
        
        // Scorri verso il basso per mostrare il nuovo messaggio
        advisorMessages.scrollTop = advisorMessages.scrollHeight;
    });
}

/**
 * Inizializza i grafici
 */
function initCharts() {
    const incomeExpenseChart = document.getElementById('income-expense-chart');
    
    if (incomeExpenseChart) {
        // Ottieni i dati per il grafico
        fetch('get_chart_data.php?type=income_expense')
        .then((response) => response.json())
        .then((data) => {
            new Chart(incomeExpenseChart, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Entrate',
                            backgroundColor: 'rgba(40, 167, 69, 0.2)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                            pointBorderColor: '#fff',
                            data: data.income
                        },
                        {
                            label: 'Uscite',
                            backgroundColor: 'rgba(220, 53, 69, 0.2)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            pointBackgroundColor: 'rgba(220, 53, 69, 1)',
                            pointBorderColor: '#fff',
                            data: data.expense
                        },
                        {
                            label: 'Risparmio',
                            backgroundColor: 'rgba(0, 123, 255, 0.2)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            pointBackgroundColor: 'rgba(0, 123, 255, 1)',
                            pointBorderColor: '#fff',
                            data: data.savings
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' €';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw + ' €';
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error loading chart data:', error);
            incomeExpenseChart.parentNode.innerHTML = '<div class="alert alert-warning">Impossibile caricare i dati del grafico</div>';
        });
    }
}

/**
 * Gestisce i messaggi flash
 */
function handleFlashMessages() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const message = urlParams.get('message');
    
    if (status && message) {
        showAlert(status === 'success' ? 'success' : 'danger', decodeURIComponent(message));
        
        // Rimuovi i parametri dall'URL
        const url = window.location.href.split('?')[0];
        window.history.replaceState({}, document.title, url);
    }
}

/**
 * Mostra un messaggio di alert
 */
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    // Aggiungi l'alert all'inizio del contenuto
    const contentWrapper = document.querySelector('.content-wrapper');
    if (contentWrapper) {
        const content = contentWrapper.querySelector('.content');
        if (content) {
            content.insertBefore(alertDiv, content.firstChild);
        } else {
            contentWrapper.insertBefore(alertDiv, contentWrapper.firstChild);
        }
    } else {
        document.body.insertBefore(alertDiv, document.body.firstChild);
    }
    
    // Nascondi automaticamente l'alert dopo 5 secondi
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 150);
    }, 5000);
}

/**
 * Mostra un toast di successo, errore o info
 */
function showToast(type, message) {
    // Rimuovi eventuali toast precedenti
    const old = document.getElementById('agtool-toast');
    if (old) old.remove();
    // Crea il toast
    const toast = document.createElement('div');
    toast.id = 'agtool-toast';
    toast.className = 'agtool-toast agtool-toast-' + type;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'times-circle' : 'info-circle')}"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.classList.add('show'); }, 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 3500);
}

/**
 * Formatta un numero come valuta
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(value);
}

/**
 * Richiama via AJAX la generazione delle ricorrenze
 */
function runRecurringAjax() {
    fetch('recurring.php?action=run_recurring')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                showToast('success', 'Transazioni ricorrenti generate: ' + data.generated);
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast('error', 'Errore nella generazione delle ricorrenze');
            }
        })
        .catch(() => showToast('error', 'Errore di connessione con il server'));
}

/**
 * Notifiche Push Browser
 */
function initPushNotifications() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.log('Push notifications non supportate');
        return;
    }
    // Registra il Service Worker
    navigator.serviceWorker.register('/sw.js')
        .then(function(registration) {
            // Chiedi permesso all'utente
            return Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    subscribeUserToPush(registration);
                } else {
                    console.log('Permesso notifiche negato');
                }
            });
        })
        .catch(function(error) {
            console.error('Errore Service Worker:', error);
        });
}

function subscribeUserToPush(registration) {
    // Recupera la chiave pubblica VAPID dal server
    fetch('get_vapid_public_key.php')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            const applicationServerKey = urlBase64ToUint8Array(data.publicKey);
            registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            })
            .then(function(subscription) {
                // Invia la subscription al server
                fetch('save_push_subscription.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(subscription)
                });
            })
            .catch(function(err) {
                console.error('Errore sottoscrizione push:', err);
            });
        });
}

// Utility per convertire la chiave VAPID
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Avvia la registrazione push all'avvio
if (window.location.pathname !== '/login.php') {
    document.addEventListener('DOMContentLoaded', function () {
        initPushNotifications();
    });
}
