const page = document.getElementsByClassName('page');
const signupButton = document.getElementById('signupButton');
const signupForm = document.getElementById('signupForm');
const loginButton = document.getElementById('loginButton');
const loginForm = document.getElementById('loginForm');

loginButton.addEventListener('click', function () {
    // revert signup
    signupForm.style.display = 'none';
    signupButton.style.backgroundColor = '#2edf84';
    signupButton.style.boxShadow = ' 0 4px 10px #2edf84';
    // login actions
    loginForm.style.display = 'flex';
    loginButton.style.backgroundColor = '#262626';
    loginButton.style.boxShadow = '0px 0px 0px';
});

signupButton.addEventListener('click', function () {
    // revert login
    loginForm.style.display = 'none';
    loginButton.style.backgroundColor = '#2edf84';
    loginButton.style.boxShadow = ' 0 4px 10px #2edf84';
    // signup actions
    signupForm.style.display = 'flex';
    signupButton.style.backgroundColor = '#262626';
    signupButton.style.boxShadow = '0px 0px 0px';
});

const phone = document.getElementById('countryCode');

function updateSelectColor2() {
    if (phone.value === "") {
      phone.classList.remove('active');
    } else {
      phone.classList.add('active');
    }
}
phone.addEventListener('change', updateSelectColor2);
updateSelectColor2();


const phoneInput = document.getElementById('phone');
phoneInput.addEventListener('input', function(e) {
      // Remove any character that is not a digit
    const digitsOnly = this.value.replace(/\D/g, '');
    if (this.value !== digitsOnly) {
    this.value = digitsOnly;
    }
});


function containsWord(str, word) {
    const regex = new RegExp(`\\b${word}\\b`, 'i'); // 'i' for case-insensitive
    return regex.test(str);
}

 function validatePhoneNumber(countryCode, phoneNumber) {
        // Remove any non-digit characters from the phone number
        const cleanedNumber = phoneNumber.replace(/\D/g, '');
        // Define regex patterns for each country code
        const patterns = {
            '+27': /^\d{9}$/, // South Africa: 10 digits
            '+264': /^\d{6,8}$/, // Namibia: 7 to 9 digits
            '+267': /^\d{6}$/, // Botswana: 7 digits
            '+263': /^\d{8}$/, // Zimbabwe: 9 digits
            '+258': /^\d{8}$/, // Mozambique: 9 digits
            '+266': /^\d{7}$/, // Lesotho: 8 digits
            '+268': /^\d{6}$/, // Eswatini: 7 digits
            '+244': /^\d{8}$/, // Angola: 9 digits
        };
        // Check if the selected country code has a defined pattern
        if (patterns[countryCode]) {
            return patterns[countryCode].test(cleanedNumber);
        }
        return false; // Invalid country code
    }

document.addEventListener('DOMContentLoaded', () => {
    const signupForm = document.getElementById('signupForm');

    signupForm.addEventListener('submit', async (event) => {
        event.preventDefault(); 

        const countryCode = document.getElementById('countryCode').value;
        const phoneNumber = document.getElementById('phone').value;
        const phoneError = document.getElementById('cphone-error');
        phoneError.innerHTML = "";
        var p=document.getElementById('phone');
        p.classList.remove("wrong");
        // Validate phone number based on country code
        if (!validatePhoneNumber(countryCode, phoneNumber)) {
            phoneError.innerHTML = "Invalid phone number format for the selected country.";
            p.classList.add("wrong");
            return;
        }

        var passerr=document.getElementById("signup-password-error");
        passerr.innerHTML="";

        var emailerr=document.getElementById("signup-email-error");
        emailerr.innerHTML="";

        var pass=document.getElementById("signup-password");
        pass.classList.remove('wrong');

        var email=document.getElementById("signup-email");
        email.classList.remove("wrong");

        const formData = new FormData(signupForm);
        const data = {
            Name: formData.get('firstName'),
            Surname: formData.get('lastName'),
            Email: formData.get('email'),
            phoneNumber: formData.get('countryCode')+formData.get('phone'),
            Password: formData.get('password'),
            api:"register"
        };
        try {
            // Send data to the API
            const response = await fetch('../../api_cos221.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            // Check if the response is ok (status in the range 200-299)
            if (!response.ok) {
                const errorData = await response.json();
                if(containsWord(errorData.data,"Password")){
                    pass.classList.add('wrong');
                    passerr.innerHTML=errorData.data;
                }
                if(containsWord(errorData.data,"email")){
                    email.classList.add("wrong");
                    emailerr.innerHTML=errorData.data;
                }
                throw new Error(errorData.data || 'Registration failed');
            }

            // Handle successful registration
            const result = await response.json();
            localStorage.setItem('apikey',result.data.apikey);
            localStorage.setItem('name',data.Name)

        } catch (error) {
            console.error('Error:', error);
        }
    });

     const loginForm = document.getElementById('loginForm');
     loginForm.addEventListener('submit',async (event)=>{
        event.preventDefault(); 
        var passerr=document.getElementById("password-error");
        passerr.innerHTML="";

        var emailerr=document.getElementById("email-error");
        emailerr.innerHTML="";

        var pass=document.getElementById("password");
        pass.classList.remove('wrong');

        var email=document.getElementById("email");
        email.classList.remove("wrong");

        const formData = new FormData(loginForm);
        const data = {
            Email: formData.get('email'),
            Password: formData.get('password'),
            api:"login"
        };
        try {
            // Send data to the API
            const response = await fetch('../../api_cos221.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            // Check if the response is ok (status in the range 200-299)
            if (!response.ok) {
                const errorData = await response.json();
                if(containsWord(errorData.data,"Password")){
                    pass.classList.add('wrong');
                    passerr.innerHTML=errorData.data;
                }
                if(containsWord(errorData.data,"email")){
                    email.classList.add("wrong");
                    emailerr.innerHTML=errorData.data;
                }
                throw new Error(errorData.data || 'Login failed');
            }

            // Handle successful registration
            const result = await response.json();
            localStorage.setItem('apikey',result.data.apikey);
            localStorage.setItem('name',result.data.name)


        } catch (error) {
            console.error('Error:', error);
        }
     });
});

