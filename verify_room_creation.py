import requests
import json

url = "http://127.0.0.1:8000/backend/api/rooms.php"
data = {
    "action": "create",
    "room_name": "Test Room",
    "username": "Test User",
    "password": "password"
}
headers = {"Content-Type": "application/json"}

response = requests.post(url, data=json.dumps(data), headers=headers)

print(response.status_code)
print(response.text)
