// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    const feedback = [];

    // Length check
    if (password.length >= 8) {
        strength += 1;
    } else {
        feedback.push("Password should be at least 8 characters long");
    }

    // Uppercase check
    if (/[A-Z]/.test(password)) {
        strength += 1;
    } else {
        feedback.push("Include at least one uppercase letter");
    }

    // Lowercase check
    if (/[a-z]/.test(password)) {
        strength += 1;
    } else {
        feedback.push("Include at least one lowercase letter");
    }

    // Number check
    if (/[0-9]/.test(password)) {
        strength += 1;
    } else {
        feedback.push("Include at least one number");
    }

    return {
        score: strength,
        maxScore: 4,
        feedback: feedback
    };
}

// Update password strength indicator
function updatePasswordStrength(password, strengthIndicator, feedbackElement) {
    const result = checkPasswordStrength(password);
    const percentage = (result.score / result.maxScore) * 100;
    
    // Update strength bar
    strengthIndicator.style.width = percentage + '%';
    
    // Update color based on strength
    if (percentage <= 25) {
        strengthIndicator.className = 'progress-bar bg-danger';
    } else if (percentage <= 50) {
        strengthIndicator.className = 'progress-bar bg-warning';
    } else if (percentage <= 75) {
        strengthIndicator.className = 'progress-bar bg-info';
    } else {
        strengthIndicator.className = 'progress-bar bg-success';
    }

    // Update feedback
    if (feedbackElement) {
        feedbackElement.innerHTML = result.feedback.map(feedback => 
            `<small class="d-block text-muted">${feedback}</small>`
        ).join('');
    }
}

// Toggle password visibility
function togglePasswordVisibility(inputId, toggleButtonId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = document.getElementById(toggleButtonId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        passwordInput.type = 'password';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
    }
}

// Initialize password fields
function initializePasswordFields() {
    // Add password visibility toggle buttons
    document.querySelectorAll('input[type="password"]').forEach(input => {
        const wrapper = document.createElement('div');
        wrapper.className = 'input-group';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        const toggleButton = document.createElement('button');
        toggleButton.className = 'btn btn-outline-secondary';
        toggleButton.type = 'button';
        toggleButton.id = input.id + '_toggle';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
        toggleButton.onclick = () => togglePasswordVisibility(input.id, toggleButton.id);
        wrapper.appendChild(toggleButton);
    });

    // Add password strength indicators
    document.querySelectorAll('input[type="password"]').forEach(input => {
        const strengthContainer = document.createElement('div');
        strengthContainer.className = 'mt-2';
        strengthContainer.innerHTML = `
            <div class="progress" style="height: 5px;">
                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <div class="password-feedback mt-1"></div>
        `;
        input.parentNode.parentNode.appendChild(strengthContainer);

        const strengthBar = strengthContainer.querySelector('.progress-bar');
        const feedbackElement = strengthContainer.querySelector('.password-feedback');

        input.addEventListener('input', () => {
            updatePasswordStrength(input.value, strengthBar, feedbackElement);
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializePasswordFields); 