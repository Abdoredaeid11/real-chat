<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Simple Chat</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; }
        #messages { border: 1px solid #ccc; height: 400px; overflow-y: auto; padding: 10px; margin-bottom: 10px; }
        .message { margin-bottom: 8px; }
        .message span.username { font-weight: bold; margin-right: 5px; }
        .message span.time { color: #777; font-size: 0.8em; margin-left: 5px; }
        #form { display: flex; gap: 5px; }
        #form input[type="text"] { flex: 1; padding: 5px; }
        #form button { padding: 5px 10px; }
    </style>
</head>
<body>
    <h1>Chat</h1>

    <div>
        <label>
            Name:
            <input type="text" id="username" placeholder="Your name">
        </label>
    </div>

    <div id="messages"></div>

    <form id="form">
        <input type="text" id="content" placeholder="Type a message..." autocomplete="off">
        <button type="submit">Send</button>
    </form>

    <script>
        const messagesEl = document.getElementById('messages');
        const form = document.getElementById('form');
        const usernameInput = document.getElementById('username');
        const contentInput = document.getElementById('content');

        let lastMessageId = null;
        let isRequestInFlight = false;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function appendMessage(message) {
            const div = document.createElement('div');
            div.className = 'message';

            const usernameSpan = document.createElement('span');
            usernameSpan.className = 'username';
            usernameSpan.textContent = message.username + ':';

            const contentSpan = document.createElement('span');
            contentSpan.textContent = ' ' + message.content;

            const timeSpan = document.createElement('span');
            timeSpan.className = 'time';
            timeSpan.textContent = message.created_at ? ' (' + message.created_at + ')' : '';

            div.appendChild(usernameSpan);
            div.appendChild(contentSpan);
            div.appendChild(timeSpan);

            messagesEl.appendChild(div);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function fetchMessages(initial = false) {
            if (isRequestInFlight) return;
            isRequestInFlight = true;

            let url = '/api/messages';
            if (!initial && lastMessageId !== null) {
                url += '?after_id=' + encodeURIComponent(lastMessageId);
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        data.forEach(msg => {
                            appendMessage(msg);
                            lastMessageId = msg.id;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching messages', error);
                })
                .finally(() => {
                    isRequestInFlight = false;
                });
        }

        function startPolling() {
            fetchMessages(true);

            setInterval(function () {
                fetchMessages(false);
            }, 1000);
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const username = usernameInput.value.trim();
            const content = contentInput.value.trim();

            if (!username || !content) {
                return;
            }

            fetch('/api/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ username: username, content: content })
            })
                .then(response => response.json())
                .then(message => {
                    appendMessage(message);
                    lastMessageId = message.id;
                    contentInput.value = '';
                    contentInput.focus();
                })
                .catch(error => {
                    console.error('Error sending message', error);
                });
        });

        startPolling();
    </script>
</body>
</html>

