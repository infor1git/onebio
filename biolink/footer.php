</div> <div id="pixModal" class="modal-backdrop">
   <div class="modal-window">
        <div class="modal-close" onclick="closePixModal()">&times;</div>
        <div id="view-input" style="display:none; width:100%;">
            <i class="fa-brands fa-pix" style="font-size:40px; color:#32bcad; margin-bottom:15px;"></i>
            <h3>Qual o valor?</h3>
            <p style="margin-bottom:20px;">Digite para gerar o QR Code.</p>
            <input type="text" id="pixValue" class="input-money" placeholder="0,00" inputmode="decimal">
            <button class="btn-primary" onclick="generateDynamicPix()">Gerar QR Code</button>
        </div>
        <div id="view-result" style="display:none; width:100%;">
            <i class="fa-brands fa-pix" style="font-size:40px; color:#32bcad; margin-bottom:15px;"></i>
            <h3 id="resultTitle">Pagamento Pix</h3>
            <p id="resultSub" style="margin-bottom:10px;">Copie o código abaixo.</p>
            <div id="qr-container"></div>
            <div id="copy-text" class="code-copy-box"></div>
            <button class="btn-primary" onclick="copyPixCode()">
                <i class="fa-regular fa-copy"></i> Copiar Código
            </button>
        </div>
    </div>
</div>

<div id="loginModal" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-close" onclick="closeLoginModal()">&times;</div>
        
        <i class="fa-solid fa-user-lock" style="font-size:40px; color:#111; margin-bottom:15px;"></i>
        <h3>Acesso Restrito</h3>
        <p style="margin-bottom:20px; font-size:0.9rem; color:#666;">Faça login com sua conta para acessar este conteúdo.</p>
        
        <input type="email" id="userEmail" class="input-money" placeholder="E-mail" style="font-size:1rem; font-weight:normal; text-align:left; margin-bottom:10px;">
        <input type="password" id="userPass" class="input-money" placeholder="Senha" style="font-size:1rem; font-weight:normal; text-align:left;">
        
        <button id="btnLoginAction" class="btn-primary" onclick="processLogin()" style="background:#111; box-shadow:none;">
            Entrar
        </button>
        
        <div id="loginMsg" style="margin-top:15px; font-size:0.85rem; color:#d32f2f;"></div>
    </div>
</div>

<div id="toast" class="toast-msg">Copiado!</div>

<?php wp_footer(); ?>

<script>
// --- FUNÇÕES DE LAYOUT (BREAKOUT) V17 - ADAPTADO PARA GRID ---
function forceBreakoutLayout() {
    const blocks = document.querySelectorAll('.content-breakout');
    if(blocks.length === 0) return;
    const viewportWidth = window.innerWidth;
    
    // Desktop: 100% da largura do grid-column (que é full-width)
    if(viewportWidth > 768) {
        blocks.forEach(el => {
            el.style.width = '100%';
            el.style.marginLeft = '0';
            el.style.marginRight = '0';
            el.style.borderRadius = '20px';
        });
        return; 
    }

    const container = document.querySelector('.app-container');
    const style = container ? window.getComputedStyle(container) : null;
    const paddingLeft = style ? parseFloat(style.paddingLeft) : 24;
    const paddingRight = style ? parseFloat(style.paddingRight) : 24;

    blocks.forEach(el => {
        el.style.width = ''; el.style.marginLeft = ''; el.style.marginRight = ''; el.style.left = ''; el.style.position = '';
        if (viewportWidth <= 768) {
            el.style.width = `calc(100% + ${paddingLeft + paddingRight}px)`;
            el.style.marginLeft = `-${paddingLeft}px`;
            el.style.marginRight = `-${paddingRight}px`;
            el.style.borderRadius = "0";
        }
    });
}
window.addEventListener('load', forceBreakoutLayout);
window.addEventListener('resize', forceBreakoutLayout);
setTimeout(forceBreakoutLayout, 500);

// --- LÓGICA DE LOGIN ---
function openLoginModal() {
    document.getElementById('loginModal').style.display = 'flex';
    setTimeout(() => document.getElementById('loginModal').classList.add('visible'), 10);
}
function closeLoginModal() {
    document.getElementById('loginModal').classList.remove('visible');
    setTimeout(() => document.getElementById('loginModal').style.display = 'none', 300);
}

function processLogin() {
    const email = document.getElementById('userEmail').value;
    const pass = document.getElementById('userPass').value;
    const btn = document.getElementById('btnLoginAction');
    const msg = document.getElementById('loginMsg');

    if(!email.includes('@')) { msg.innerText = "Digite um e-mail válido."; return; }
    if(!pass) { msg.innerText = "Digite sua senha."; return; }

    btn.innerText = "Verificando...";
    btn.disabled = true;
    msg.innerText = "";

    const formData = new FormData();
    formData.append('action', 'mybiolink_process_login');
    formData.append('email', email);
    formData.append('password', pass);
    formData.append('nonce', mybiolink_vars.nonce);

    fetch(mybiolink_vars.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            msg.style.color = "green";
            msg.innerText = "Sucesso! Recarregando...";
            setTimeout(() => location.reload(), 1000);
        } else {
            msg.style.color = "red";
            msg.innerText = data.data || "Erro de login.";
            btn.innerText = "Entrar";
            btn.disabled = false;
        }
    })
    .catch(err => {
        msg.innerText = "Erro de conexão.";
        btn.innerText = "Entrar";
        btn.disabled = false;
    });
}

function doLogout() {
    if(!confirm("Deseja sair da sua área privada?")) return;
    const formData = new FormData();
    formData.append('action', 'mybiolink_logout');
    fetch(mybiolink_vars.ajax_url, { method: 'POST', body: formData })
    .then(() => location.reload());
}

// --- FUNÇÕES DE PIX ---
let currentPixKey = '';
const modal = document.getElementById('pixModal');
const viewInput = document.getElementById('view-input');
const viewResult = document.getElementById('view-result');
const qrBox = document.getElementById('qr-container');
const copyBox = document.getElementById('copy-text');

function openPixStatic(key, imgUrl) {
    resetModal();
    viewResult.style.display = 'block';
    document.getElementById('resultTitle').innerText = "Chave Pix";
    document.getElementById('resultSub').innerText = "Copie a chave abaixo.";
    copyBox.innerText = key;
    qrBox.innerHTML = '';
    
    if(imgUrl && imgUrl.length > 5) {
        qrBox.innerHTML = '<img src="'+imgUrl+'" style="max-width:100%; border-radius:10px;">';
    } else {
        qrBox.style.display = 'none';
    }
    showPixModal();
}

function openPixDynamic(key) {
    resetModal();
    currentPixKey = key;
    viewInput.style.display = 'block';
    document.getElementById('pixValue').value = '';
    showPixModal();
}

function generateDynamicPix() {
    let rawVal = document.getElementById('pixValue').value;
    let val = 0;
    if(rawVal) val = rawVal.replace(',', '.');
    
    const payload = createPixPayload(currentPixKey, val);
    
    viewInput.style.display = 'none';
    viewResult.style.display = 'block';
    qrBox.style.display = 'flex'; 
    
    document.getElementById('resultTitle').innerText = (val > 0) ? "R$ " + rawVal : "Pagamento Pix";
    document.getElementById('resultSub').innerText = "Escaneie ou copie.";

    qrBox.innerHTML = '';
    
    new QRCode(qrBox, { text: payload, width: 200, height: 200, colorDark: "#1f2937", correctLevel : QRCode.CorrectLevel.L });
    copyBox.innerText = payload;

    setTimeout(() => {
        const imgs = qrBox.getElementsByTagName('img');
        if(imgs.length > 0) { imgs[0].style.margin = "0 auto"; imgs[0].style.display = "block"; }
    }, 50);
}

function showPixModal() {
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('visible'), 10);
}
function closePixModal() {
    modal.classList.remove('visible');
    setTimeout(() => modal.style.display = 'none', 300);
}
function resetModal() {
    viewInput.style.display = 'none';
    viewResult.style.display = 'none';
}
function copyPixCode() {
    navigator.clipboard.writeText(copyBox.innerText).then(() => {
        const t = document.getElementById('toast');
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    });
}
function shareProfile() {
    if(navigator.share) navigator.share({title:document.title, url:window.location.href});
    else { navigator.clipboard.writeText(window.location.href); alert('Link copiado!'); }
}
document.getElementById('pixValue').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, "");
    v = (v/100).toFixed(2) + "";
    v = v.replace(".", ",").replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,").replace(/(\d)(\d{3}),/g, "$1.$2,");
    e.target.value = v == "0,00" ? "" : v;
});
function createPixPayload(key, value) {
    const f = (id, v) => id + v.length.toString().padStart(2,'0') + v;
    let p = f('00','01') + f('26', f('00','br.gov.bcb.pix') + f('01',key)) + f('52','0000') + f('53','986');
    if (parseFloat(value) > 0) p += f('54', parseFloat(value).toFixed(2));
    p += f('58','BR') + f('59','RECEBEDOR') + f('60','CIDADE') + f('62', f('05','***')) + '6304';
    let crc = 0xFFFF;
    for(let c=0; c<p.length; c++) {
        crc ^= p.charCodeAt(c) << 8;
        for(let i=0; i<8; i++) { if(crc & 0x8000) crc = (crc << 1) ^ 0x1021; else crc = crc << 1; }
    }
    return p + (crc & 0xFFFF).toString(16).toUpperCase().padStart(4,'0');
}
</script>
</body>
</html>