<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gab_T task manager</title>
        <style>
            *{
                box-sizing:border-box;
                font-family:Arial, Helvetica, sans-serif;
                color:white;
            }
            .container{
                width:100%;
                min-height:100vh;
                min-width: fit-content;
                background:#ffffff;
                display:flex;
                flex-direction:row;
                justify-content:space-evenly;
            }
            .card{
                width:300px;
                max-height: fit-content;
                background:#000000;
                border:3px solid #000;
                box-shadow:12px 12px 0 #000;
                overflow:hidden;
                transform:translate(-6px,-6px);
                transition:.2s;
                gap: 10px;
            }

            .card:hover{
                transform:translate(-10px,-10px);
                box-shadow:10px 10px 0 #000;
            }
            .cardsvd{
                width:300px;
                max-height: fit-content;
                background:#000000;
                border:3px solid #000;
                box-shadow:12px 12px 0 #c21616;
                overflow:hidden;
                transform:translate(-6px,-6px);
                transition:.2s;
                gap:10px;
            }
            .cardsvd:hover{
                transform:translate(-10px,-10px);
                box-shadow:10px 10px 0 #69f04e;
            }

            .head{
                font-size:14px;
                font-weight:900;
                color:#000;
                background:#fff;
                padding:5px 12px;
                border-bottom:3px solid #000;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .content{
                padding:8px 12px;
            }

            .button{
                color:#000;
                padding:5px 10px;
                border:3px #000;
                box-shadow:3px 3px 0 #000;
                background:#ffffff;
                cursor:pointer;
                transition:.15s;
                margin-top: 5px;
                text-decoration: none;
                display: inline-block;
            }

            .button:hover{
                transform:translate(2px,2px);
                box-shadow:1px 1px 0 #000;
                background:#69f04e;
            }

            .logout-btn {
                background: #f74c9c;
                color: #fff;
                border: none;
                padding: 3px 8px;
                font-size: 11px;
                cursor: pointer;
                text-decoration: none;
            }

            #landing{
                opacity:0;
                transition:opacity 1.5s ease;
                width:100%;
                position:relative;
                z-index:1;
            }

            #landing.show{
                opacity:1;
            }

            #landing::before{
                content:"";
                position:absolute;
                inset:0;
                background-image:radial-gradient(circle at 1px 1px, rgb(0, 0, 0) 1px, transparent 0);
                background-size:25px 25px;
                animation:movePattern 40s linear infinite;
                z-index:-1;
            }

            @keyframes movePattern{
                from{background-position:0 0;}
                to{background-position:500px 500px;}
            }
            .card-grid{
                display:grid;
                grid-template-columns:repeat(2,1fr);
                gap:10px;
                padding: 20px;
            }
            input{
                color: #000;
                width: 100%;
                margin-bottom: 8px;
            }
            .finlist, .svdlist, .delayed, #displayArea {
                list-style-type:none;
                padding-left: 0;
            }
            li {
                margin-bottom: 5px;
            }
        </style>    
    </head>
    <body>
        <div class="container">
            <div id="landing" class="show">
                <div class="card-grid">
                    <div class="card">
                        <div class="head">
                            <span>Welcome, <?php echo $username; ?>!</span>
                            <a href="logout.php" class="logout-btn">Logout</a>
                        </div>
                        <div class="content">
                            <label>Enter tasks</label>
                            <input type="text" id="userInput" placeholder="Enter Tasks"><br>
                            <label>Set task duration (hours)</label>
                            <input type="number" id="taskduration" placeholder="Completion time in hours" min="0" step="0.1"><br>
                            <button class="button" id="myButton">Add to list</button>
                        </div>
                    </div>
                    <div class="card">
                        <div class="head">Tasks</div>  
                        <div class="content">
                            <ul id="displayArea"></ul>
                            <button class="button" id="completeBtn">Mark as complete!</button>
                            <button class="button" id="saveTsk">Save Task for Later </button>
                        </div> 
                    </div>
                    <div class="card">
                        <div class="head">Completed Tasks</div>  
                        <div class="content">
                            <ul class="finlist" id="displayAreafn"></ul>
                            <button class="button" id="refreshBtn">Refresh!</button>
                        </div> 
                    </div>
                    <div class="cardsvd">
                        <div class="head">Saved Tasks</div>
                        <div class="content">
                            <ul class="svdlist" id="displayAreasv"></ul>
                            <button class="button" id="completeBtnsvd">Mark as complete!</button>
                            <button class="button" id="refreshbtnsvd">Clear Saved Tasks</button>
                        </div>
                    </div>
                    <div class="card">
                        <div class="head">Delayed Tasks</div>  
                        <div class="content">
                            <ul class="delayed" id="displayAreaDl"></ul>
                            <button class="button" id="refreshBtnDl">Clear Delayed Tasks!</button>
                        </div> 
                    </div>
                </div>
            </div>
        </div>
        <script>
            let tasks = [];
            let completedTasks = [];
            let svdTasks = [];
            let incompleteTasks = [];

            const inputField = document.getElementById('userInput');
            const inputButton = document.getElementById('myButton');
            const completeButton = document.getElementById('completeBtn');
            const refreshButton = document.getElementById('refreshBtn');
            const saveButton = document.getElementById('saveTsk'); 
            const taskDurationInput = document.getElementById('taskduration');
            const refreshbtnsvd = document.getElementById('refreshbtnsvd');
            const refreshbtndl = document.getElementById('refreshBtnDl');
            const completeBtnsvd = document.getElementById('completeBtnsvd');

            const displaySpace = document.getElementById('displayArea');
            const finishedTasks = document.getElementById('displayAreafn');
            const savedTasks = document.getElementById('displayAreasv');
            const delayedTasks = document.getElementById('displayAreaDl');

            // API Helper Functions
            async function fetchTasks() {
                try {
                    const res = await fetch('tasks_api.php?action=fetch');
                    const data = await res.json();
                    
                    // Clear active timers before re-populating
                    tasks.forEach(clearTaskTimer);
                    
                    tasks = data.filter(t => t.status === 'active');
                    completedTasks = data.filter(t => t.status === 'completed');
                    svdTasks = data.filter(t => t.status === 'saved');
                    incompleteTasks = data.filter(t => t.status === 'delayed');

                    // Set up timers for fetched active tasks
                    tasks.forEach(setupTaskTimer);

                    rendertasks();
                } catch (err) {
                    console.error("Error fetching tasks:", err);
                }
            }

            async function updateTaskStatus(taskId, status) {
                await fetch('tasks_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update_status', id: taskId, status: status })
                });
            }

            async function clearTasksByStatus(status) {
                await fetch('tasks_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'clear_status', status: status })
                });
            }

            function rendertasks() {
                displaySpace.innerHTML = tasks.map(t => `<li>${t.name}${t.duration ? ` (${t.duration}h duration)` : ''}</li>`).join("");
                finishedTasks.innerHTML = completedTasks.map(t => `<li>${t.name}</li>`).join("");
                savedTasks.innerHTML = svdTasks.map(t => `<li>${t.name}</li>`).join("");
                delayedTasks.innerHTML = incompleteTasks.map(t => `<li>${t.name}</li>`).join("");
            }

            function clearTaskTimer(task) {
                if (task && task.timerId) {
                    clearTimeout(task.timerId);
                }
            }

            function setupTaskTimer(task) {
                if (task.duration) {
                    const durationMs = task.duration * 60 * 60 * 1000;
                    task.timerId = setTimeout(async () => {
                        alert(`TASK NOT COMPLETE: "${task.name}" duration expired!`);
                        
                        const index = tasks.indexOf(task);
                        if (index > -1) {
                            tasks.splice(index, 1);
                        }
                        
                        incompleteTasks.push(task);
                        await updateTaskStatus(task.id, 'delayed');
                        rendertasks();
                    }, durationMs);
                }
            }

            inputButton.addEventListener('click', async () => {
                const taskName = inputField.value.trim();
                const durationVal = parseFloat(taskDurationInput.value);

                if (taskName === "") {
                    alert("Enter tasks");
                    return;
                }

                const duration = !isNaN(durationVal) && durationVal > 0 ? durationVal : null;

                const res = await fetch('tasks_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'create', name: taskName, duration: duration })
                });
                const responseData = await res.json();

                const newTask = {
                    id: responseData.id,
                    name: taskName,
                    duration: duration,
                    status: 'active',
                    timerId: null
                };

                setupTaskTimer(newTask);
                tasks.push(newTask);
                
                inputField.value = "";
                taskDurationInput.value = "";
                rendertasks();
            });

            completeButton.addEventListener('click', async () => {
                if (tasks.length === 0) {
                    alert("No pending tasks");
                    return;
                }
                const completedTask = tasks.shift();
                clearTaskTimer(completedTask);
                completedTask.status = 'completed';
                completedTasks.push(completedTask);
                
                await updateTaskStatus(completedTask.id, 'completed');
                rendertasks();
            });

            refreshButton.addEventListener('click', async () => {
                completedTasks.length = 0;
                await clearTasksByStatus('completed');
                rendertasks();
            });     

            saveButton.addEventListener('click', async () => {
                if (tasks.length === 0) {
                    alert("No tasks to save");
                    return;
                }
                const savedTask = tasks.shift();
                clearTaskTimer(savedTask);
                savedTask.status = 'saved';
                svdTasks.push(savedTask);
                
                await updateTaskStatus(savedTask.id, 'saved');
                rendertasks();
            });

            refreshbtnsvd.addEventListener('click', async () => {
                svdTasks.length = 0;
                await clearTasksByStatus('saved');
                rendertasks();
            });

            refreshbtndl.addEventListener('click', async () => {
                incompleteTasks.length = 0;
                await clearTasksByStatus('delayed');
                rendertasks();
            }); 

            completeBtnsvd.addEventListener('click', async () => {
                if (svdTasks.length === 0) {
                    alert("No saved tasks to complete");
                    return;
                }
                const completedSavedTask = svdTasks.shift();
                completedSavedTask.status = 'completed';
                completedTasks.push(completedSavedTask);
                
                await updateTaskStatus(completedSavedTask.id, 'completed');
                rendertasks();
            });

            // Initial load from MySQL database
            fetchTasks();
        </script> 
    </body>
</html>