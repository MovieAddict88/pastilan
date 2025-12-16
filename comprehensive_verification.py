import requests
import json

def verify_room_creation():
    url = "http://127.0.0.1:8000/backend/api/rooms.php"
    data = {
        "action": "create",
        "room_name": "Test Room",
        "username": "Test User",
        "password": "password"
    }
    headers = {"Content-Type": "application/json"}

    response = requests.post(url, data=json.dumps(data), headers=headers)

    print(f"Room creation status code: {response.status_code}")
    print(f"Room creation response: {response.text}")
    assert response.status_code == 200
    assert "success" in response.json()
    assert response.json()["success"] is True

def verify_dashboard_access():
    url = "http://127.0.0.1:8000/backend/admin/dashboard.php"
    response = requests.get(url)

    print(f"Dashboard access status code: {response.status_code}")
    assert response.status_code == 200

if __name__ == "__main__":
    verify_room_creation()
    verify_dashboard_access()
