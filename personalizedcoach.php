<?php
include 'header.php';
include 'db_connect.php';

// Fetch games from DB
$games = [];
$result = $conn->query("SELECT game_id, name FROM games");
while ($row = $result->fetch_assoc()) {
  $games[] = $row;
}

// Fetch budgets
$budgets = [];
$result = $conn->query("SELECT MIN(hourly_rate) AS min_rate, MAX(hourly_rate) AS max_rate FROM coaches WHERE verification_flag=TRUE AND account_active=TRUE");
if ($row = $result->fetch_assoc()) {
  $min = floor($row['min_rate'] / 50) * 50;
  $max = ceil($row['max_rate'] / 50) * 50;
  for ($i = $min; $i <= $max; $i += 50) {
    $budgets[] = $i;
  }
}


$goals = ['Improving aim', 'Team coordination', 'Better mechanics and positioning'];
?>

<div class="container">
  <div class="row">
    <div class="col-lg-12">
      <div class="page-content">

        <!-- Banner -->
        <div class="main-banner">
          <div class="row">
            <div class="col-lg-7">
              <div class="header-text">
                <h6><em>Personalized Coach Selection</em></h6>
                <h4>Answer a few quick questions</h4>
                <div class="main-button">
                  <a href="#chatbot">Get Started</a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Chatbot Conversation -->
        <div class="escp_content1" id="chatbot">
          <div class="heading-section">
            <h4><em>Coach Finder Chat</em></h4>
          </div>
          <div class="chat-window p-3 rounded" id="chatWindow">
            <!-- Initial system bubble only -->
            <div class="chat-bubble system">
              <p>Hello! Let’s find your perfect coach.</p>
            </div>
          </div>

          <!-- Chat Input -->
          <div class="chat-input mt-3 p-2 border-top d-flex">
            <input type="text" id="chatMessage" class="form-control" placeholder="Type your answer and press Enter...  Or ... Type restart to start selection" />
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php
include 'footer.php';
?>

<script>
  const games = <?= json_encode($games) ?>; // includes id + name
  const budgets = <?= json_encode($budgets) ?>;
  const goals = <?= json_encode($goals) ?>;


  let answers = {
    game: null,
    game_id: null,
    role: null,
    role_id: null,
    rank: null,
    rank_id: null,
    budget: null,
    goal: null
  };
  let currentQuestionIndex = 0;

  const questions = [{
      key: "game",
      prompt: "Which game do you play?",
      options: games.map(g => g.name)
    },
    {
      key: "role",
      prompt: "What’s your role?",
      options: []
    },
    {
      key: "rank",
      prompt: "And your current rank?",
      options: []
    },
    {
      key: "budget",
      prompt: "What’s your budget per hour?",
      options: budgets.map(b => b + " SAR")
    },
    {
      key: "goal",
      prompt: "Finally, what’s your main goal?",
      options: goals
    }
  ];

  const chatWindow = document.getElementById('chatWindow');
  const chatInput = document.getElementById('chatMessage');
  const BOT_DELAY_MS = 1000;

  function appendBubble(text, type = "system") {
    const bubble = document.createElement('div');
    bubble.classList.add('chat-bubble', type);
    bubble.innerHTML = `<p>${text}</p>`;
    chatWindow.appendChild(bubble);
    chatWindow.scrollTop = chatWindow.scrollHeight;
  }

  function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  async function botReply(text) {
    await wait(BOT_DELAY_MS);
    appendBubble(text, "system");
  }

  function showOptions(options) {
    if (!options || options.length === 0) return;
    const optDiv = document.createElement('div');
    optDiv.classList.add('options');
    options.forEach(opt => {
      const btn = document.createElement('button');
      btn.classList.add('option-btn');
      btn.textContent = opt;
      btn.dataset.value = opt;
      btn.onclick = () => handleAnswer(opt);
      optDiv.appendChild(btn);
    });
    chatWindow.appendChild(optDiv);
    chatWindow.scrollTop = chatWindow.scrollHeight;
  }

  async function restartChat() {
  // Clear chat window
  chatWindow.innerHTML = `
    <div class="chat-bubble system">
      <p>Hello! Let’s find your perfect coach.</p>
    </div>
  `;

  // Reset state
  answers = { game:null, game_id:null, role:null, role_id:null, rank:null, rank_id:null, budget:null, goal:null };
  currentQuestionIndex = 0;

  // Start again
  await botReply(questions[0].prompt);
  showOptions(questions[0].options);
}


  async function handleAnswer(value) {

    if (value.toLowerCase() === "restart") {
      await restartChat();
      return;
    }

    const q = questions[currentQuestionIndex];
    const validOptions = q.options.map(o => (typeof o === "string" ? o.toLowerCase() : o.name.toLowerCase()));

    if (!validOptions.includes(value.toLowerCase())) {
      await botReply("Not allowed. Please choose one of: " + q.options.map(o => (typeof o === "string" ? o : o.name)).join(", "));
      showOptions(q.options.map(o => (typeof o === "string" ? o : o.name)));
      return;
    }

    appendBubble(value, "user");

    if (q.key === "game") {
      const selected = games.find(g => g.name.toLowerCase() === value.toLowerCase());
      if (selected) {
        answers.game_id = selected.game_id;
        answers.game = selected.name;
      }
    }

    if (q.key === "role") {
      const selected = questions[currentQuestionIndex].options.find(o => o.name.toLowerCase() === value.toLowerCase());
      if (selected) {
        answers.role_id = selected.id;
        answers.role = selected.name;
      }
    }

    if (q.key === "rank") {
      const selected = questions[currentQuestionIndex].options.find(o => o.name.toLowerCase() === value.toLowerCase());
      if (selected) {
        answers.rank_id = selected.id;
        answers.rank = selected.name;
      }
    }

    if (q.key === "budget") {
      answers.budget = parseInt(value); // strip "SAR"
    }

    if (q.key === "goal") {
      answers.goal = value;
    }

    currentQuestionIndex++;
    if (currentQuestionIndex < questions.length) {
      const nextQ = questions[currentQuestionIndex];
      await botReply(nextQ.prompt);

      if (nextQ.key === "role" && answers.game_id) {
        await botReply("Fetching roles...");
        fetch("get_roles.php?game_id=" + answers.game_id)
          .then(res => res.json())
          .then(data => {
            console.log("Roles fetched:", data);
            nextQ.options = data.map(r => ({
              id: r.role_id,
              name: r.role_name
            }));
            showOptions(nextQ.options.map(o => o.name));
          });
      } else if (nextQ.key === "rank" && answers.game_id) {
        await botReply("Fetching ranks...");
        fetch("get_ranks.php?game_id=" + answers.game_id)
          .then(res => res.json())
          .then(data => {
            console.log("Ranks fetched:", data);
            nextQ.options = data.map(r => ({
              id: r.rank_id,
              name: r.rank_name
            }));
            showOptions(nextQ.options.map(o => o.name));
          });
      } else {
        showOptions(nextQ.options);
      }
    } else {
      await botReply("Perfect! Here are some coaches that match your preferences:");
      fetch("get_coaches.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(answers)
        })
        .then(res => res.text())
        .then(html => {
          const div = document.createElement('div');
          div.innerHTML = html;
          chatWindow.appendChild(div);
          chatWindow.scrollTop = chatWindow.scrollHeight;
        });
    }
  }

  chatInput.addEventListener('keypress', async function(e) {
    if (e.key === 'Enter' && chatInput.value.trim() !== '') {
      await handleAnswer(chatInput.value.trim());
      chatInput.value = '';
    }
  });

  // Start with first question + clickable options
  (async function startChat() {
    await botReply(questions[0].prompt);
    showOptions(questions[0].options);
  })();
</script>