<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบโชว์ผลงานวิชาการ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Sarabun', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
        }
        
        .room-card {
            transition: all 0.3s ease;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        }
        
        .room-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .nav-button {
            transition: all 0.3s ease;
        }
        
        .nav-button:hover {
            transform: translateY(-1px);
        }
        
        .admin-input {
            transition: all 0.3s ease;
        }
        
        .admin-input:focus {
            transform: scale(1.02);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center py-4 sm:h-16">
                <div class="flex items-center mb-4 sm:mb-0">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 text-center">ระบบโชว์ผลงานวิชาการ</h1>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 w-full sm:w-auto">
                    <button onclick="showPage('main')" class="nav-button bg-green-600 hover:bg-green-700 text-white px-4 py-3 sm:py-2 rounded-lg font-bold text-base sm:text-sm w-full sm:w-auto">
                        🏠 หน้าหลัก
                    </button>
                    <button onclick="showPage('admin')" class="nav-button bg-green-500 hover:bg-green-600 text-white px-4 py-3 sm:py-2 rounded-lg font-bold text-base sm:text-sm w-full sm:w-auto">
                        ⚙️ จัดการห้อง
                    </button>
                    <button onclick="showPage('schedule')" class="nav-button bg-green-700 hover:bg-green-800 text-white px-4 py-3 sm:py-2 rounded-lg font-bold text-base sm:text-sm w-full sm:w-auto">
                        📅 กำหนดการ
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Page -->
    <div id="mainPage" class="page">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center mb-8">
                <h2 class="text-5xl font-black text-gray-900 mb-4 drop-shadow-sm">งานประชุมวิชาการกระทรวงสาธารณสุข ประจำปี 2568</h2>
                <div class="bg-gradient-to-r from-[#D0EBE0] to-[#DFF5E1] p-6 rounded-xl mb-6 mx-auto max-w-4xl shadow-lg">
                    <p class="text-xl text-gray-800 font-bold">ยกระดับการสาธารณสุขไทยสุขภาพแข็งแรงทุกวัย เศรษฐกิจสุขภาพไทยมั่นคง</p>
                </div>
                <p class="text-2xl text-gray-700 font-bold">จำนวนห้องทั้งหมด: <span id="totalRooms" class="font-black text-green-700 text-3xl">20</span> ห้อง</p>
            </div>
            
            <div id="roomsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Rooms will be generated here -->
            </div>
        </div>
    </div>

    <!-- Admin Page -->
    <div id="adminPage" class="page hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">จัดการห้องนำเสนอ</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                    <!-- Add Room Section -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-4 sm:p-6 rounded-lg">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">➕ เพิ่มห้องใหม่</h3>
                        <div class="space-y-4">
                            <input type="text" id="newRoomTopic" placeholder="เรื่องบรรยาย" 
                                   class="admin-input w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <input type="text" id="newRoomPresenter" placeholder="ชื่อผู้นำเสนอ" 
                                   class="admin-input w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <input type="text" id="newRoomTime" placeholder="เวลา (เช่น 09:00-10:00)" 
                                   class="admin-input w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" id="newRoomLive" class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                                <label for="newRoomLive" class="text-gray-700">🔴 กำลังบรรยาย (LIVE)</label>
                            </div>
                            <button onclick="addRoom()" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium transition-all duration-300 hover:shadow-lg">
                                เพิ่มห้อง
                            </button>
                        </div>
                    </div>

                    <!-- Room Count Section -->
                    <div class="bg-gradient-to-br from-emerald-50 to-green-50 p-4 sm:p-6 rounded-lg">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">🔢 จัดการจำนวนห้อง</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">จำนวนห้องปัจจุบัน:</span>
                                <span id="currentRoomCount" class="font-bold text-emerald-600 text-xl">20</span>
                            </div>
                            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                                <button onclick="removeLastRoom()" class="flex-1 bg-red-500 hover:bg-red-600 text-white py-3 rounded-lg font-bold transition-all duration-300">
                                    ➖ ลดห้อง
                                </button>
                                <button onclick="addEmptyRoom()" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white py-3 rounded-lg font-bold transition-all duration-300">
                                    ➕ เพิ่มห้องว่าง
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Existing Rooms -->
                <div class="mt-8">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">📝 แก้ไขห้องที่มีอยู่</h3>
                    <div id="editRoomsList" class="space-y-6 max-h-96 overflow-y-auto">
                        <!-- Edit rooms will be generated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Page -->
    <div id="schedulePage" class="page hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">กำหนดการนำเสนอทั้งหมด</h2>
                
                <!-- Add Schedule Item -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 sm:p-6 rounded-lg mb-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">➕ เพิ่มกิจกรรมใหม่</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <input type="text" id="scheduleTitle" placeholder="ชื่อกิจกรรม" 
                               class="admin-input p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <input type="text" id="scheduleTime" placeholder="เวลา" 
                               class="admin-input p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <button onclick="addScheduleItem()" class="bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium transition-all duration-300">
                            เพิ่มกิจกรรม
                        </button>
                    </div>
                </div>

                <!-- Schedule List -->
                <div id="scheduleList" class="space-y-4">
                    <!-- Schedule items will be generated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Room Detail Modal -->
    <div id="roomModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 id="modalRoomTitle" class="text-3xl font-black text-gray-900"></h3>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-3xl font-bold">&times;</button>
                </div>
            </div>
            <div class="p-6">
                <div id="modalRoomContent" class="space-y-6">
                    <!-- Room content will be populated here -->
                </div>
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <button onclick="showScheduleModal()" class="w-full bg-green-600 hover:bg-green-700 text-white py-4 px-6 rounded-xl font-bold text-lg transition-all duration-300 hover:shadow-lg">
                        📅 ดูรายละเอียดกำหนดการทั้งหมด
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div id="scheduleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-3xl font-black text-gray-900">📅 กำหนดการทั้งหมด</h3>
                    <button onclick="closeScheduleModal()" class="text-gray-500 hover:text-gray-700 text-3xl font-bold">&times;</button>
                </div>
            </div>
            <div class="p-6">
                <div id="modalScheduleContent" class="space-y-4">
                    <!-- Schedule content will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data storage
        let rooms = [];
        let schedule = [];

        // Initialize data from localStorage or create default
        function initializeData() {
            const savedRooms = localStorage.getItem('academicRooms');
            const savedSchedule = localStorage.getItem('academicSchedule');
            
            if (savedRooms) {
                rooms = JSON.parse(savedRooms);
            } else {
                // Create default 20 rooms
                rooms = [];
                const sampleTopics = [
                    'การพัฒนาระบบสุขภาพดิจิทัล', 'นวัตกรรมการดูแลผู้สูงอายุ', 'การป้องกันโรคติดต่อในชุมชน', 'เทคโนโลยี AI ในการแพทย์',
                    'การจัดการข้อมูลสุขภาพ', 'การส่งเสริมสุขภาพจิต', 'ระบบการแพทย์ทางไกล', 'การพัฒนายาสมุนไพร',
                    'การบริหารโรงพยาบาล', 'การดูแลสุขภาพแม่และเด็ก', 'การป้องกันโรคเรื้อรัง', 'นโยบายสาธารณสุข',
                    'การจัดการภาวะฉุกเฉิน', 'การพัฒนาบุคลากรทางการแพทย์', 'ระบบประกันสุขภาพ', 'การวิจัยทางการแพทย์',
                    'การดูแลผู้ป่วยติดเตียง', 'การส่งเสริมการออกกำลังกาย', 'การจัดการโรคอ้วน', 'การพัฒนาวัคซีน'
                ];
                const samplePresenters = [
                    'ดร.สมชาย ใจดี', 'ผศ.วิไล สุขใส', 'นพ.ประยุทธ์ รักษา', 'ดร.สุดา เก่งกาจ',
                    'รศ.มานะ ขยัน', 'ดร.นิภา ปราณี', 'นพ.วิชัย มั่นคง', 'ผศ.สมหญิง ดีใจ',
                    'ดร.ธนา วิทยา', 'นพ.สุรชัย เฉลียว', 'ดร.พิมพ์ใจ สง่า', 'รศ.บุญมี ใหม่',
                    'ดร.อรุณ สว่าง', 'ผศ.มาลี หวาน', 'นพ.ชาญ ฉลาด', 'ดร.สิริ งาม',
                    'รศ.วิทย์ เก่ง', 'ดร.นภา สุข', 'นพ.ธีร ดี', 'ผศ.จิรา ใส'
                ];
                
                for (let i = 1; i <= 20; i++) {
                    rooms.push({
                        id: i,
                        topic: sampleTopics[i-1],
                        presenter: samplePresenters[i-1],
                        time: `${String(9 + Math.floor((i-1)/4)).padStart(2, '0')}:00-${String(10 + Math.floor((i-1)/4)).padStart(2, '0')}:00`,
                        isLive: i <= 2, // First 2 rooms are live by default
                        name: sampleTopics[i-1], // Keep for backward compatibility
                        sessions: [
                            {
                                id: 1,
                                topic: sampleTopics[i-1],
                                presenter: samplePresenters[i-1],
                                time: `${String(9 + Math.floor((i-1)/4)).padStart(2, '0')}:00-${String(10 + Math.floor((i-1)/4)).padStart(2, '0')}:00`
                            }
                        ]
                    });
                }
                saveRooms();
            }
            
            if (savedSchedule) {
                schedule = JSON.parse(savedSchedule);
            } else {
                schedule = [
                    { id: 1, title: 'เปิดงานนำเสนอผลงานวิชาการ', time: '08:30-09:00' },
                    { id: 2, title: 'การนำเสนอผลงานรอบที่ 1', time: '09:00-10:30' },
                    { id: 3, title: 'พักรับประทานอาหารว่าง', time: '10:30-10:45' },
                    { id: 4, title: 'การนำเสนอผลงานรอบที่ 2', time: '10:45-12:15' }
                ];
                saveSchedule();
            }
        }

        // Save functions
        function saveRooms() {
            localStorage.setItem('academicRooms', JSON.stringify(rooms));
        }

        function saveSchedule() {
            localStorage.setItem('academicSchedule', JSON.stringify(schedule));
        }

        // Page navigation
        function showPage(pageName) {
            document.querySelectorAll('.page').forEach(page => page.classList.add('hidden'));
            document.getElementById(pageName + 'Page').classList.remove('hidden');
            
            if (pageName === 'main') {
                renderRooms();
            } else if (pageName === 'admin') {
                renderAdminPage();
            } else if (pageName === 'schedule') {
                renderSchedule();
            }
        }

        // Render rooms grid
        function renderRooms() {
            const grid = document.getElementById('roomsGrid');
            const totalRoomsSpan = document.getElementById('totalRooms');
            
            totalRoomsSpan.textContent = rooms.length;
            
            grid.innerHTML = rooms.map(room => `
                <div class="room-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 cursor-pointer hover:shadow-xl transform hover:scale-105" onclick="openRoomModal(${room.id})">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-2xl font-black text-gray-900">ห้อง ${room.id}</h3>
                        ${room.isLive ? 
                            '<span class="bg-red-100 text-red-800 px-4 py-2 rounded-full text-sm font-bold animate-pulse">🔴 LIVE กำลังบรรยาย</span>' : 
                            '<span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-full text-sm font-bold">⏸️ รอการนำเสนอ</span>'
                        }
                    </div>
                    <div class="space-y-3 mb-4">
                        <h4 class="font-black text-gray-900 text-lg">เรื่องบรรยาย:</h4>
                        <p class="text-gray-700 leading-relaxed font-semibold text-base">${room.topic || room.name}</p>
                        
                        <h4 class="font-black text-gray-900 text-lg mt-4">ผู้นำเสนอ:</h4>
                        <p class="text-gray-700 font-semibold text-base">${room.presenter || 'ยังไม่ระบุ'}</p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-base text-gray-600 font-bold">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                ${room.time}
                            </div>
                            <span class="text-green-600 font-bold text-sm">👆 คลิกดูรายละเอียด</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Admin functions
        function renderAdminPage() {
            document.getElementById('currentRoomCount').textContent = rooms.length;
            renderEditRoomsList();
        }

        function renderEditRoomsList() {
            const editList = document.getElementById('editRoomsList');
            editList.innerHTML = rooms.map(room => {
                const sessions = room.sessions || [{
                    id: 1,
                    topic: room.topic || room.name,
                    presenter: room.presenter,
                    time: room.time
                }];
                
                return `
                <div class="bg-white border-2 border-gray-200 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-2xl font-black text-gray-900">🏛️ ห้อง ${room.id}</h4>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" ${room.isLive ? 'checked' : ''} onchange="updateRoom(${room.id}, 'isLive', this.checked)"
                                       class="w-5 h-5 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500">
                                <label class="text-base font-bold text-gray-700">🔴 LIVE</label>
                            </div>
                            <button onclick="deleteRoom(${room.id})" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors">
                                🗑️ ลบห้อง
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h5 class="text-lg font-bold text-gray-800">📋 รายการบรรยาย (${sessions.length} เรื่อง)</h5>
                            <button onclick="addSession(${room.id})" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors">
                                ➕ เพิ่มเรื่อง
                            </button>
                        </div>
                        
                        ${sessions.map((session, index) => `
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border border-green-200">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-bold text-gray-800">เรื่องที่ ${index + 1}</span>
                                    ${sessions.length > 1 ? `
                                        <button onclick="removeSession(${room.id}, ${session.id})" class="bg-red-400 hover:bg-red-500 text-white px-3 py-1 rounded text-xs font-bold transition-colors">
                                            ❌ ลบ
                                        </button>
                                    ` : ''}
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">หัวข้อบรรยาย:</label>
                                        <input type="text" value="${session.topic || ''}" onchange="updateSession(${room.id}, ${session.id}, 'topic', this.value)"
                                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">ผู้นำเสนอ:</label>
                                        <input type="text" value="${session.presenter || ''}" onchange="updateSession(${room.id}, ${session.id}, 'presenter', this.value)"
                                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">เวลา:</label>
                                        <input type="text" value="${session.time || ''}" onchange="updateSession(${room.id}, ${session.id}, 'time', this.value)"
                                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent font-medium">
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            }).join('');
        }

        function addRoom() {
            const topic = document.getElementById('newRoomTopic').value.trim();
            const presenter = document.getElementById('newRoomPresenter').value.trim();
            const time = document.getElementById('newRoomTime').value.trim();
            const isLive = document.getElementById('newRoomLive').checked;
            
            if (!topic || !time) {
                alert('กรุณากรอกเรื่องบรรยายและเวลาให้ครบถ้วน');
                return;
            }
            
            const newId = Math.max(...rooms.map(r => r.id), 0) + 1;
            rooms.push({ 
                id: newId, 
                topic, 
                presenter, 
                time, 
                isLive,
                name: topic // Keep for backward compatibility
            });
            
            document.getElementById('newRoomTopic').value = '';
            document.getElementById('newRoomPresenter').value = '';
            document.getElementById('newRoomTime').value = '';
            document.getElementById('newRoomLive').checked = false;
            
            saveRooms();
            renderAdminPage();
        }

        function addEmptyRoom() {
            const newId = Math.max(...rooms.map(r => r.id), 0) + 1;
            rooms.push({
                id: newId,
                topic: `เรื่องนำเสนอที่ ${newId}`,
                presenter: '',
                time: '09:00-10:00',
                isLive: false,
                name: `เรื่องนำเสนอที่ ${newId}` // Keep for backward compatibility
            });
            
            saveRooms();
            renderAdminPage();
        }

        function removeLastRoom() {
            if (rooms.length > 0) {
                rooms.pop();
                saveRooms();
                renderAdminPage();
            }
        }

        function updateRoom(id, field, value) {
            const room = rooms.find(r => r.id === id);
            if (room) {
                room[field] = value;
                saveRooms();
            }
        }

        function deleteRoom(id) {
            if (confirm('คุณต้องการลบห้องนี้หรือไม่?')) {
                rooms = rooms.filter(r => r.id !== id);
                saveRooms();
                renderAdminPage();
            }
        }

        // Schedule functions
        function renderSchedule() {
            const scheduleList = document.getElementById('scheduleList');
            scheduleList.innerHTML = schedule.map(item => `
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-lg border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">${item.title}</h3>
                            <p class="text-green-600 font-medium">${item.time}</p>
                        </div>
                        <button onclick="deleteScheduleItem(${item.id})" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition-colors">
                            ลบ
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function addScheduleItem() {
            const title = document.getElementById('scheduleTitle').value.trim();
            const time = document.getElementById('scheduleTime').value.trim();
            
            if (!title || !time) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }
            
            const newId = Math.max(...schedule.map(s => s.id), 0) + 1;
            schedule.push({ id: newId, title, time });
            
            document.getElementById('scheduleTitle').value = '';
            document.getElementById('scheduleTime').value = '';
            
            saveSchedule();
            renderSchedule();
        }

        function deleteScheduleItem(id) {
            if (confirm('คุณต้องการลบกิจกรรมนี้หรือไม่?')) {
                schedule = schedule.filter(s => s.id !== id);
                saveSchedule();
                renderSchedule();
            }
        }

        // Modal functions
        function openRoomModal(roomId) {
            const room = rooms.find(r => r.id === roomId);
            if (!room) return;
            
            document.getElementById('modalRoomTitle').textContent = `ห้อง ${room.id}`;
            
            const sessions = room.sessions || [{
                id: 1,
                topic: room.topic || room.name,
                presenter: room.presenter,
                time: room.time
            }];
            
            const content = `
                <div class="space-y-6">
                    ${room.isLive ? 
                        '<div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg"><div class="flex items-center"><span class="text-red-600 font-bold text-lg animate-pulse">🔴 กำลังบรรยายอยู่ในขณะนี้</span></div></div>' : 
                        '<div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded-lg"><div class="flex items-center"><span class="text-gray-600 font-bold text-lg">⏸️ รอการนำเสนอ</span></div></div>'
                    }
                    
                    <div class="space-y-4">
                        <h4 class="text-2xl font-black text-gray-900 mb-4">📋 รายการบรรยายในห้องนี้:</h4>
                        ${sessions.map((session, index) => `
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-xl border-l-4 border-green-500">
                                <div class="flex items-start justify-between mb-3">
                                    <h5 class="text-lg font-black text-gray-900">เรื่องที่ ${index + 1}</h5>
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-bold">${session.time}</span>
                                </div>
                                <h6 class="font-bold text-gray-800 mb-2">หัวข้อ:</h6>
                                <p class="text-gray-700 font-semibold mb-3">${session.topic}</p>
                                <h6 class="font-bold text-gray-800 mb-2">ผู้นำเสนอ:</h6>
                                <p class="text-gray-700 font-semibold">${session.presenter || 'ยังไม่ระบุ'}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            
            document.getElementById('modalRoomContent').innerHTML = content;
            document.getElementById('roomModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('roomModal').classList.add('hidden');
        }
        
        function showScheduleModal() {
            const content = schedule.map(item => `
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-xl border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h4 class="text-xl font-black text-gray-900 mb-2">${item.title}</h4>
                            <p class="text-green-700 font-bold text-lg">${item.time}</p>
                        </div>
                    </div>
                </div>
            `).join('');
            
            document.getElementById('modalScheduleContent').innerHTML = content;
            document.getElementById('scheduleModal').classList.remove('hidden');
        }
        
        function closeScheduleModal() {
            document.getElementById('scheduleModal').classList.add('hidden');
        }

        // Session management functions
        function addSession(roomId) {
            const room = rooms.find(r => r.id === roomId);
            if (!room) return;
            
            if (!room.sessions) {
                room.sessions = [{
                    id: 1,
                    topic: room.topic || room.name,
                    presenter: room.presenter,
                    time: room.time
                }];
            }
            
            const newSessionId = Math.max(...room.sessions.map(s => s.id), 0) + 1;
            room.sessions.push({
                id: newSessionId,
                topic: 'เรื่องใหม่',
                presenter: '',
                time: '09:00-10:00'
            });
            
            saveRooms();
            renderEditRoomsList();
        }
        
        function removeSession(roomId, sessionId) {
            const room = rooms.find(r => r.id === roomId);
            if (!room || !room.sessions) return;
            
            if (room.sessions.length > 1) {
                room.sessions = room.sessions.filter(s => s.id !== sessionId);
                saveRooms();
                renderEditRoomsList();
            } else {
                alert('ต้องมีอย่างน้อย 1 เรื่องในห้อง');
            }
        }
        
        function updateSession(roomId, sessionId, field, value) {
            const room = rooms.find(r => r.id === roomId);
            if (!room || !room.sessions) return;
            
            const session = room.sessions.find(s => s.id === sessionId);
            if (session) {
                session[field] = value;
                // Update main room data for backward compatibility
                if (room.sessions.length === 1) {
                    room[field] = value;
                }
                saveRooms();
            }
        }

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            initializeData();
            showPage('main');
        });
    </script>
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'97a25466b445893b',t:'MTc1NzAzOTY4OS4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>
