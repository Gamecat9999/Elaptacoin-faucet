from flask import request
from flask_limiter import Limiter

def get_remote_address():
    """Return the remote address of the client."""
    return request.remote_addr

limiter = Limiter(key_func=get_remote_address)

def apply_rate_limiter(app):
    """Apply rate limiting to the faucet route."""
    @app.route('/faucet', methods=['POST'])
    @limiter.limit("1/minute")
    def faucet():
        """Handle the faucet transaction."""
        try:
            # Your code for the faucet route goes here
            return "Faucet claimed successfully!"
        except Exception as e:
            # Handle any exceptions that may occur during the faucet transaction
            return f"Error: {str(e)}"