from flask import Flask, render_template, request
import requests
import time
import datetime

app = Flask(__name__)

# Define the login URL and the target URL
login_url = 'https://wallet.stormsurge.xyz/login'
target_url = 'https://wallet.stormsurge.xyz/transaction'

# Create a session
session = requests.Session()

# Define the login payload
payload = {
    'username': 'USERNAME_HERE',
    'password': 'PASSWORD_HERE'
}

# Log in to the website
response = session.post(login_url, data=payload)

# Check if login was successful
if response.ok:
    print("Login successful!")
else:
    print("Login failed!")

# Cooldown duration in seconds
cooldown_duration = 24 * 60 * 60  # 1 day in seconds

# Function to get the current timestamp
def get_timestamp():
    return int(time.time())

# Function to read cooldown data from the file
def read_cooldown_data():
    cooldown_data = {}
    try:
        with open('cooldown.txt', 'r') as file:
            for line in file:
                parts = line.strip().split(',')
                if len(parts) == 2:  # Make sure there are two parts
                    username, timestamp = parts
                    cooldown_data[username] = int(timestamp)
                else:
                    print(f"Invalid line in cooldown.txt: {line}")
    except FileNotFoundError:
        pass  # File doesn't exist, create it later
    return cooldown_data

# Function to write cooldown data to the file
def write_cooldown_data(cooldown_data):
    with open('cooldown.txt', 'w') as file:
        for username, timestamp in cooldown_data.items():
            file.write(f'{username},{timestamp}\n')

@app.route('/', methods=['GET', 'POST'])
def index():
    cooldown_data = read_cooldown_data()

    if request.method == 'POST':
        username = request.form.get('username')

        # Check if the user is on cooldown
        if username in cooldown_data:
            timestamp = cooldown_data[username]
            remaining_time = cooldown_duration - (get_timestamp() - timestamp)
            if remaining_time > 0:
                # Calculate remaining time in days, hours, minutes, and seconds
                days, remainder = divmod(remaining_time, 24 * 60 * 60)
                hours, remainder = divmod(remainder, 60 * 60)
                minutes, seconds = divmod(remainder, 60)
                return f"You are on cooldown for {days} days, {hours} hours, {minutes} minutes, and {seconds} seconds!"

        # Define the faucet payload
        faucet_payload = {
            'username': username,
            'password': 'Jbllc100',
            'address': 'DuckyPolice',
            'amount': '0.001'  # Set the amount to 1
        }

        # Submit the faucet form
        faucet_response = session.post(target_url, data=faucet_payload)

        # Check the faucet response
        if faucet_response.ok:
            # Add user to cooldown
            cooldown_data[username] = get_timestamp()
            write_cooldown_data(cooldown_data)
            return "Faucet transaction successful!"
        else:
            return "Faucet transaction failed!"

    # Render the HTML template
    return render_template('index.html') 

if __name__ == '__main__':
    app.run(debug=True)
