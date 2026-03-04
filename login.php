<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — MediCore HMS</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --blue-900:#0d2137;--blue-700:#0f4c81;--blue-500:#1565c0;--blue-400:#1976d2;--blue-100:#e3f2fd;
  --green-500:#2e7d32;--green-100:#e8f5e9;--red-500:#c62828;--red-100:#ffebee;
  --gray-900:#111827;--gray-700:#374151;--gray-500:#6b7280;--gray-300:#d1d5db;--gray-100:#f9fafb;--white:#ffffff;
  --radius:12px;
}
body{font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#0d2137 0%,#1565c0 50%,#0f4c81 100%);padding:20px;overflow:hidden;position:relative}
body::before{content:'';position:absolute;inset:-50%;
  background:radial-gradient(ellipse at 30% 30%,rgba(21,101,192,.3) 0%,transparent 60%),
             radial-gradient(ellipse at 70% 70%,rgba(13,33,55,.4) 0%,transparent 60%);
  animation:pulse 8s ease-in-out infinite alternate}
@keyframes pulse{0%{transform:scale(1)}100%{transform:scale(1.06)}}
.card{position:relative;z-index:1;background:var(--white);border-radius:20px;
  box-shadow:0 25px 70px rgba(0,0,0,.25);width:100%;max-width:460px;overflow:hidden;
  animation:slideUp .5s cubic-bezier(.34,1.56,.64,1)}
@keyframes slideUp{from{opacity:0;transform:translateY(40px) scale(.95)}to{opacity:1;transform:none}}
.card-header{background:linear-gradient(135deg,var(--blue-900),var(--blue-700));color:var(--white);padding:32px 40px 24px;text-align:center}
.logo{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:6px}
.logo-icon{width:46px;height:46px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center}
.logo-text{font-size:1.9rem;font-weight:700;letter-spacing:-.5px}
.logo-sub{font-size:.82rem;opacity:.72;margin-bottom:18px}
.tabs{display:flex;background:rgba(0,0,0,.2);border-radius:10px;padding:4px;gap:0}
.tab{flex:1;padding:9px 10px;border:none;background:transparent;color:rgba(255,255,255,.65);font-size:.82rem;
  font-weight:500;border-radius:7px;cursor:pointer;transition:all .2s;font-family:'Inter',sans-serif}
.tab.active{background:var(--white);color:var(--blue-700);font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.15)}
.card-body{padding:32px 40px}
.panel{display:none}.panel.active{display:block}
.fgroup{margin-bottom:18px}
.flabel{display:block;font-size:.78rem;font-weight:700;color:var(--gray-700);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em}
.iwrap{position:relative}
.iicon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--gray-500);pointer-events:none;display:flex}
.finput{width:100%;padding:11px 42px;border:2px solid var(--gray-300);border-radius:var(--radius);
  font-size:.93rem;font-family:'Inter',sans-serif;color:var(--gray-900);transition:border-color .2s,box-shadow .2s;background:var(--gray-100)}
.finput:focus{outline:none;border-color:var(--blue-400);background:var(--white);box-shadow:0 0 0 4px rgba(21,101,192,.1)}
.finput.valid{border-color:var(--green-500)}.finput.invalid{border-color:var(--red-500)}
.eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray-500);padding:0;line-height:0}
/* password strength */
.sbar{height:4px;border-radius:2px;background:var(--gray-300);margin-top:8px;overflow:hidden}
.sfill{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0}
.slabel{font-size:.72rem;margin-top:4px;font-weight:600;min-height:1rem}
.reqs{background:var(--gray-100);border-radius:10px;padding:12px 14px;margin-top:8px}
.req{display:flex;align-items:center;gap:8px;font-size:.75rem;color:var(--gray-500);margin-bottom:5px;transition:color .2s}
.req:last-child{margin-bottom:0}
.dot{width:16px;height:16px;border-radius:50%;border:2px solid var(--gray-300);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s}
.req.met{color:var(--green-500)}.req.met .dot{background:var(--green-500);border-color:var(--green-500)}
.req.met .dot::after{content:'';width:4px;height:3px;border-left:2px solid white;border-bottom:2px solid white;transform:rotate(-45deg) translateY(-1px);display:block}
/* alert */
.alert{padding:11px 14px;border-radius:10px;font-size:.85rem;margin-bottom:18px;display:flex;align-items:center;gap:9px}
.alert-error{background:var(--red-100);color:var(--red-500);border:1px solid #ef9a9a}
.alert-success{background:var(--green-100);color:var(--green-500);border:1px solid #a5d6a7}
.alert-info{background:var(--blue-100);color:var(--blue-700);border:1px solid #90caf9}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,var(--blue-500),var(--blue-700));color:white;border:none;
  border-radius:var(--radius);font-size:.95rem;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;
  transition:all .2s;box-shadow:0 4px 15px rgba(21,101,192,.35)}
.btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(21,101,192,.45)}
.btn:active{transform:none}
.demo{background:var(--blue-100);border:1px solid #90caf9;border-radius:10px;padding:12px 14px;margin-top:18px}
.demo h4{font-size:.72rem;font-weight:700;color:var(--blue-700);text-transform:uppercase;letter-spacing:.05em;margin-bottom:7px}
.drow{display:flex;justify-content:space-between;align-items:center;font-size:.78rem;color:var(--gray-700);padding:2px 0}
.drow code{background:white;padding:2px 8px;border-radius:5px;font-weight:700;color:var(--blue-700);font-family:monospace}
.card-footer{text-align:center;padding:14px 40px 20px;font-size:.75rem;color:var(--gray-500);border-top:1px solid var(--gray-300)}
.tlink{text-align:center;margin-top:14px;font-size:.82rem;color:var(--gray-500)}
.tlink a{color:var(--blue-500);font-weight:600;text-decoration:none;cursor:pointer}
.tlink a:hover{text-decoration:underline}
.hidden{display:none!important}
</style>
</head>
<body>

<div class="card">
  <div class="card-header">
    <div class="logo">
      <div class="logo-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
          <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
        </svg>
      </div>
      <div class="logo-text">MediCore</div>
    </div>
    <div class="logo-sub">Hospital Management System</div>
    <div class="tabs" id="main-tabs">
      <button class="tab active" onclick="switchTab('login','user')" id="tab-user">👤 User Login</button>
      <button class="tab" onclick="switchTab('login','admin')" id="tab-admin">🔐 Admin Login</button>
    </div>
  </div>

  <div class="card-body">
    <div id="alert-box"></div>

    <!-- USER LOGIN -->
    <div class="panel active" id="panel-login-user">
      <div class="fgroup">
        <label class="flabel">Username</label>
        <div class="iwrap">
          <span class="iicon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <input type="text" id="user-un" class="finput" placeholder="Enter your username" autocomplete="username">
          <button type="button" class="eye" style="display:none"></button>
        </div>
      </div>
      <div class="fgroup">
        <label class="flabel">Password</label>
        <div class="iwrap">
          <span class="iicon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="user-pw" class="finput" placeholder="Enter your password" autocomplete="current-password">
          <button type="button" class="eye" onclick="toggleEye('user-pw',this)" title="Show/hide">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <button class="btn" onclick="doLogin('user')">Sign In as User</button>
      <div class="demo">
        <h4>Demo Credentials</h4>
        <div class="drow"><span>Username:</span><code>staff</code></div>
        <div class="drow"><span>Password:</span><code>Staff@2024!</code></div>
      </div>
    </div>

    <!-- ADMIN LOGIN -->
    <div class="panel" id="panel-login-admin">
      <div class="fgroup">
        <label class="flabel">Admin Email</label>
        <div class="iwrap">
          <span class="iicon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
          <input type="email" id="admin-em" class="finput" placeholder="admin@medicore.com" autocomplete="email">
          <button type="button" class="eye" style="display:none"></button>
        </div>
      </div>
      <div class="fgroup">
        <label class="flabel">Admin Password</label>
        <div class="iwrap">
          <span class="iicon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="admin-pw" class="finput" placeholder="Enter admin password" autocomplete="current-password">
          <button type="button" class="eye" onclick="toggleEye('admin-pw',this)" title="Show/hide">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <button class="btn" onclick="doLogin('admin')">Sign In as Admin</button>
      <div class="demo">
        <h4>Admin Credentials</h4>
        <div class="drow"><span>Email:</span><code>admin@medicore.com</code></div>
        <div class="drow"><span>Password:</span><code>Admin@2024!</code></div>
      </div>
    </div>

    <!-- REGISTER -->
    <div class="panel" id="panel-register">
      <div class="fgroup">
        <label class="flabel">Full Name</label>
        <div class="iwrap">
          <span class="iicon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <input type="text" id="reg-name" class="finput" placeholder="Your full name">
          <button type="button" class="eye" style="display:none"></button>
        </div>
      </div>
      <div class="fgroup">
        <label class="flabel">Username</label>
        <div class="iwrap">
          <span class="iicon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <input type="text" id="reg-un" class="finput" placeholder="Choose a username">
          <button type="button" class="eye" style="display:none"></button>
        </div>
      </div>
      <div class="fgroup">
        <label class="flabel">Email</label>
        <div class="iwrap">
          <span class="iicon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
          <input type="email" id="reg-email" class="finput" placeholder="your@email.com">
          <button type="button" class="eye" style="display:none"></button>
        </div>
      </div>
      <div class="fgroup">
        <label class="flabel">Password</label>
        <div class="iwrap">
          <span class="iicon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="reg-pw" class="finput" placeholder="Create a strong password" oninput="checkStr()">
          <button type="button" class="eye" onclick="toggleEye('reg-pw',this)">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="sbar"><div class="sfill" id="s-fill"></div></div>
        <div class="slabel" id="s-label"></div>
        <div class="reqs">
          <div class="req" id="r-len"><div class="dot"></div>At least 8 characters</div>
          <div class="req" id="r-up"><div class="dot"></div>One uppercase letter (A–Z)</div>
          <div class="req" id="r-lo"><div class="dot"></div>One lowercase letter (a–z)</div>
          <div class="req" id="r-dg"><div class="dot"></div>One digit (0–9)</div>
          <div class="req" id="r-sp"><div class="dot"></div>One special character (!@#$%^&*…)</div>
        </div>
      </div>
      <div class="fgroup">
        <label class="flabel">Confirm Password</label>
        <div class="iwrap">
          <span class="iicon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="reg-cf" class="finput" placeholder="Repeat your password">
          <button type="button" class="eye" onclick="toggleEye('reg-cf',this)">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <button class="btn" onclick="doRegister()">Create Account</button>
    </div>

    <div class="tlink" id="lnk-reg">No account? <a onclick="switchTab('register')">Register here</a></div>
    <div class="tlink hidden" id="lnk-login">Already have an account? <a onclick="switchTab('login','user')">Sign in</a></div>
  </div>

  <div class="card-footer">&copy; <?php echo date('Y'); ?> MediCore HMS — CSE 4508 RDBMS</div>
</div>

<!-- PHP session bridge -->
<form id="sf" method="POST" action="auth_session.php" style="display:none">
  <input type="hidden" id="sf-role"  name="role">
  <input type="hidden" id="sf-un"    name="username">
  <input type="hidden" id="sf-name"  name="full_name">
</form>

<script>
// ── LocalStorage helpers ──────────────────────────────────────────────
const UK = 'medicore_users', SK = 'medicore_session';

const DEFAULTS = [
  {id:1,username:'admin',email:'admin@medicore.com',password:'Admin@2024!',role:'admin',full_name:'System Administrator'},
  {id:2,username:'staff',email:'staff@medicore.com',password:'Staff@2024!',role:'user', full_name:'Hospital Staff'}
];

function getUsers(){
  const r = localStorage.getItem(UK);
  if(!r){ localStorage.setItem(UK, JSON.stringify(DEFAULTS)); return DEFAULTS; }
  return JSON.parse(r);
}
function saveUsers(u){ localStorage.setItem(UK, JSON.stringify(u)); }

function setSession(u){
  localStorage.setItem(SK, JSON.stringify({...u, logged_in:true, ts:Date.now()}));
  document.getElementById('sf-role').value  = u.role;
  document.getElementById('sf-un').value    = u.username;
  document.getElementById('sf-name').value  = u.full_name;
  document.getElementById('sf').submit();
}

// ── Password rules ────────────────────────────────────────────────────
const RULES = [
  {id:'r-len', test:p=>p.length>=8},
  {id:'r-up',  test:p=>/[A-Z]/.test(p)},
  {id:'r-lo',  test:p=>/[a-z]/.test(p)},
  {id:'r-dg',  test:p=>/\d/.test(p)},
  {id:'r-sp',  test:p=>/[!@#$%^&*()\-_=+\[\]{};':"\\|,.<>/?`~]/.test(p)}
];

function validatePw(p){
  let score=0;
  const r={};
  RULES.forEach(({id,test})=>{ r[id]=test(p); if(r[id]) score++; });
  return {r, score, valid:score===5};
}

function checkStr(){
  const p = document.getElementById('reg-pw').value;
  const {r, score} = validatePw(p);
  RULES.forEach(({id})=>{
    document.getElementById(id).classList.toggle('met', r[id]);
  });
  const steps=[
    {pct:0,  bg:'#e0e0e0', txt:''},
    {pct:20, bg:'#ef5350', txt:'🔴 Very Weak'},
    {pct:40, bg:'#ff9800', txt:'🟠 Weak'},
    {pct:60, bg:'#fdd835', txt:'🟡 Fair'},
    {pct:80, bg:'#66bb6a', txt:'🟢 Strong'},
    {pct:100,bg:'#2e7d32', txt:'✅ Very Strong'}
  ][score];
  const f=document.getElementById('s-fill'), l=document.getElementById('s-label');
  f.style.width=steps.pct+'%'; f.style.background=steps.bg;
  l.textContent=steps.txt; l.style.color=steps.bg;
}

// ── UI helpers ────────────────────────────────────────────────────────
let curTab='login', curRole='user';

function switchTab(tab, role){
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(b=>b.classList.remove('active'));
  clearAlert();
  if(tab==='register'){
    document.getElementById('panel-register').classList.add('active');
    document.getElementById('main-tabs').style.display='none';
    document.getElementById('lnk-reg').classList.add('hidden');
    document.getElementById('lnk-login').classList.remove('hidden');
    curTab='register';
  } else {
    document.getElementById('main-tabs').style.display='flex';
    document.getElementById('lnk-reg').classList.remove('hidden');
    document.getElementById('lnk-login').classList.add('hidden');
    curTab='login'; curRole=role||'user';
    if(role==='admin'){
      document.getElementById('panel-login-admin').classList.add('active');
      document.getElementById('tab-admin').classList.add('active');
    } else {
      document.getElementById('panel-login-user').classList.add('active');
      document.getElementById('tab-user').classList.add('active');
    }
  }
}

function toggleEye(id, btn){
  const inp=document.getElementById(id);
  const vis=inp.type==='password';
  inp.type=vis?'text':'password';
  btn.innerHTML=vis
    ?`<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
    :`<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
}

function showAlert(msg,type='error'){
  const icons={error:'❌',success:'✅',info:'ℹ️'};
  document.getElementById('alert-box').innerHTML=`<div class="alert alert-${type}"><span>${icons[type]}</span><span>${msg}</span></div>`;
}
function clearAlert(){ document.getElementById('alert-box').innerHTML=''; }

// ── Login ─────────────────────────────────────────────────────────────
function doLogin(role){
  clearAlert();
  const users=getUsers();
  if(role==='admin'){
    const em=document.getElementById('admin-em').value.trim();
    const pw=document.getElementById('admin-pw').value;
    if(!em||!pw){ showAlert('Please enter your email and password.'); return; }
    const u=users.find(x=>x.role==='admin'&&x.email===em);
    if(!u){ showAlert('No admin account found with this email.'); return; }
    if(u.password!==pw){ showAlert('Incorrect password. Please try again.'); return; }
    showAlert('Admin login successful! Redirecting…','success');
    setTimeout(()=>setSession(u),700);
  } else {
    const un=document.getElementById('user-un').value.trim();
    const pw=document.getElementById('user-pw').value;
    if(!un||!pw){ showAlert('Please enter your username and password.'); return; }
    const u=users.find(x=>x.username===un);
    if(!u){ showAlert('Username not found. Please check and try again.'); return; }
    if(u.password!==pw){ showAlert('Incorrect password. Please try again.'); return; }
    if(u.role==='admin'){ showAlert('Admin accounts must use the Admin Login tab.','info'); return; }
    showAlert('Login successful! Redirecting…','success');
    setTimeout(()=>setSession(u),700);
  }
}

// ── Register ──────────────────────────────────────────────────────────
function doRegister(){
  clearAlert();
  const name=document.getElementById('reg-name').value.trim();
  const un=document.getElementById('reg-un').value.trim();
  const em=document.getElementById('reg-email').value.trim();
  const pw=document.getElementById('reg-pw').value;
  const cf=document.getElementById('reg-cf').value;
  if(!name||!un||!em||!pw||!cf){ showAlert('Please fill in all fields.'); return; }
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)){ showAlert('Please enter a valid email address.'); return; }
  const {valid,r}=validatePw(pw);
  if(!valid){
    const miss=[];
    if(!r['r-len']) miss.push('≥8 characters');
    if(!r['r-up'])  miss.push('uppercase letter');
    if(!r['r-lo'])  miss.push('lowercase letter');
    if(!r['r-dg'])  miss.push('digit');
    if(!r['r-sp'])  miss.push('special character');
    showAlert('Password needs: '+miss.join(', ')+'.'); return;
  }
  if(pw!==cf){ showAlert('Passwords do not match.'); return; }
  const users=getUsers();
  if(users.find(x=>x.username===un)){ showAlert('Username already taken. Choose another.'); return; }
  if(users.find(x=>x.email===em)){ showAlert('An account with this email already exists.'); return; }
  const nu={id:Date.now(),username:un,email:em,password:pw,role:'user',full_name:name,created_at:new Date().toISOString()};
  users.push(nu); saveUsers(users);
  showAlert('Account created! You can now sign in.','success');
  setTimeout(()=>switchTab('login','user'),1400);
}

// Enter key
document.addEventListener('keydown',e=>{
  if(e.key!=='Enter') return;
  if(curTab==='register') doRegister();
  else doLogin(curRole);
});

// Clear session on explicit logout
if (new URLSearchParams(location.search).get('logged_out') === '1') {
  localStorage.removeItem('medicore_session');
  showAlert('You have been logged out successfully.', 'info');
}

// Init defaults
getUsers();
</script>
</body>
</html>
