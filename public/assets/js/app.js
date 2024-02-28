document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('add-message').addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            this.submit();
        }
    });

    console.log('bep')
});