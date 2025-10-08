<?php
// (c) J~Net 2025 - Simple AI STT + Dummy Response (PHP + Whisper.cpp)
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>🎙️ AI Voice Chatbot</title>
<style>
body{background:#111;color:#eee;font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;margin:0;
  scrollbar-color: #444 #111;
  scrollbar-width: thin;
  }
textarea{width:80%;height:150px;background:#222;color:#0f0;padding:10px;border:1px solid #333;border-radius:10px;font-size:16px;resize:none}
button{margin-top:10px;padding:10px 20px;border:none;border-radius:10px;cursor:pointer;font-size:16px}
#listenBtn,#clearBtn,#copyBtn{background:#333;color:#fff}
#listenBtn.active,#clearBtn:active,#copyBtn:active{background:#0a0;color:#000}
/* (c) J~Net 2025
   Global Dark Scrollbar Theme */

/* Chrome, Edge, Safari */
* {
  scrollbar-width: thin;
  scrollbar-color: #444 #111;
}

::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}

::-webkit-scrollbar-track {
  background: #111;
}

::-webkit-scrollbar-thumb {
  background-color: #444;
  border-radius: 5px;
  border: 2px solid #111;
}

::-webkit-scrollbar-thumb:hover {
  background-color: #666;
}

</style>

</head>
<body>
<h2>🎧 AI Voice Chatbot</h2>
<textarea id="chatBox" placeholder="Initializing..."></textarea>
<button id="listenBtn">🎤 Listen</button>

<button id="copyBtn" class="inputbtn">Copy</button>

<button id="clearBtn" class="inputbtn">Clear</button>

<script>
// ✅ Copy to Clipboard for textarea
document.getElementById("copyBtn").addEventListener("click",()=>{
  const chatBox=document.getElementById("chatBox");
  const text=chatBox.value; // <-- use .value for textarea
  navigator.clipboard.writeText(text).then(()=>{
    const btn=document.getElementById("copyBtn");
    const old=btn.textContent;
    btn.textContent="Copied!";
    setTimeout(()=>btn.textContent=old,1000);
  }).catch(err=>{
    console.error("Clipboard error:",err);
  });
});

// ✅ Clear #chatBox content
document.getElementById("clearBtn").addEventListener("click",()=>{
  const chatBox=document.getElementById("chatBox");
  chatBox.value="";
  const btn=document.getElementById("clearBtn");
  const old=btn.textContent;
  btn.textContent="Cleared!";
  setTimeout(()=>btn.textContent=old,1000);
});

</script>

<script>
// Auto-scrolls chatBox to bottom
function scrollChatBox(){
  const chatBox=document.getElementById("chatBox");
  chatBox.scrollTop=chatBox.scrollHeight;
}

// ---- Wait for full page and ignore Cloudflare Rocket Loader ----
function initChatbot(){
  console.log("🚀 Initializing chatbot JS...");
  const chatBox=document.getElementById("chatBox");
  const listenBtn=document.getElementById("listenBtn");
  chatBox.value="Ready. Press 🎤 Listen to start.";

  let isListening=false,mediaRecorder,audioChunks=[];
  let audioCtx,analyser,lastSoundTime;

  const FIXED_MIC_ID="alsa_input.usb-ME6S_MS_N-B_R-UN__3db_ME6S-00.mono-fallback";

  listenBtn.onclick=async()=>{
    if(isListening){ stopListening(); return; }
    console.log("➡️ Listen button clicked");
    chatBox.value+="\n[🎯 Listen button clicked]";
    try{
      await startListening();
    }catch(e){
      console.error("[⚠️ Mic Error]",e);
      chatBox.value+=`\n[⚠️ Mic Error] ${e.name}: ${e.message}`;
    }
  };

  async function startListening(){
    console.log("🟢 Requesting microphone permissions...");
    chatBox.value+="\n[🔒 Requesting mic permissions...]";

    const stream=await getMicStream();
    if(!stream) throw new Error("No microphone available");

    isListening=true;
    listenBtn.classList.add("active");
    listenBtn.textContent="🛑 Stop Listening";

    audioChunks=[];
    mediaRecorder=new MediaRecorder(stream);
    mediaRecorder.ondataavailable=e=>{if(e.data.size>0) audioChunks.push(e.data);}
    mediaRecorder.onstop=()=>{processAudio();}
    mediaRecorder.start();
    console.log("🎙 Recording started");
    chatBox.value+="\n[🎙 Recording started]";

    // Silence detection
    audioCtx=new AudioContext();
    analyser=audioCtx.createAnalyser();
    const src=audioCtx.createMediaStreamSource(stream);
    src.connect(analyser);
    const data=new Uint8Array(analyser.frequencyBinCount);
    lastSoundTime=Date.now();

    const loop=() => {
      analyser.getByteFrequencyData(data);
      const vol=data.reduce((a,b)=>a+b)/data.length;
      if(vol>10) lastSoundTime=Date.now();
      if(Date.now()-lastSoundTime>2500 && isListening){
        console.log("🔇 Silence detected → stopping recording");
        chatBox.value+="\n[🔇 Silence detected → stopping]";
        stopListening();
      }
      if(isListening) requestAnimationFrame(loop);
    };
    loop();

    chatBox.value+="\n[🎤 Mic Active]\n";
    console.log("🎧 Mic active and monitoring for silence...");
    scrollChatBox();
  }

  async function getMicStream(){
    try{
      // Request permission with dummy getUserMedia to unlock enumerateDevices
      await navigator.mediaDevices.getUserMedia({audio:true});

      const devices=await navigator.mediaDevices.enumerateDevices();
      const mics=devices.filter(d=>d.kind==="audioinput");
      console.log("Available mics:", mics);
      chatBox.value+="\n[🎤 Mics detected: "+ (mics.length?mics.map(d=>d.label).join(", "):"None") +"]";

      // Try fixed mic first
      let micDevice=mics.find(d=>d.deviceId===FIXED_MIC_ID);
      if(!micDevice && mics.length) micDevice=mics[0]; // fallback to first available
      if(!micDevice) throw new Error("No usable microphone found");

      console.log("🎙 Using mic:", micDevice.label||"Unknown");
      const stream=await navigator.mediaDevices.getUserMedia({
        audio:{
          deviceId:{exact: micDevice.deviceId},
          channelCount:1,
          sampleRate:48000,
          echoCancellation:true,
          noiseSuppression:true
        }
      });
      return stream;
    }catch(err){
      console.error("🚫 getMicStream() error:",err);
      chatBox.value+="\n[🚫 getMicStream() error] "+err.message;
      return null;
    }
  }

  function stopListening(){
    if(!isListening) return;
    isListening=false;
    listenBtn.classList.remove("active");
    listenBtn.textContent="🎤 Listen";
    if(mediaRecorder && mediaRecorder.state!=="inactive") mediaRecorder.stop();
    console.log("⏹ Recording stopped");
    chatBox.value+="\n[⏹ Recording stopped]";
  }


async function processAudio(){
  const blob=new Blob(audioChunks,{type:"audio/webm"});
  console.log("⏳ Sending audio to server for transcription...");
  chatBox.value+="\n[⏳ Transcribing audio...]";
  scrollChatBox();

  const formData=new FormData();
  formData.append("file",blob,"speech.webm");

  try{
    const res=await fetch("transcribe.php",{method:"POST",body:formData});
    const data=await res.json();
    if(data.text){
      console.log("🗣 Transcription result:",data.text);
      chatBox.value+=`\nYou: ${data.text}\n`;
      scrollChatBox();
      const response="(Dummy reply for now)";
      chatBox.value+=`\nBot: ${response}\n`;
      scrollChatBox();
      console.log("💬 Dummy bot response sent");
      speak(response);
    }else{
      console.warn("❌ No transcription result received");
      chatBox.value+="\n[❌ No transcription result]";
      scrollChatBox();
    }
  }catch(err){
    console.error("[⚠️ Network Error]",err);
    chatBox.value+=`\n[⚠️ Network Error] ${err}`;
    scrollChatBox();
  }
}

  function speak(text){
    const utter=new SpeechSynthesisUtterance(text);
    utter.rate=1;
    speechSynthesis.speak(utter);
    console.log("🗣 Speaking:",text);
  }
}

// ---- Wait for window.load to ensure all scripts (Rocket Loader) are finished ----
if(document.readyState==="complete"){
  initChatbot();
}else{
  window.addEventListener("load",()=>{ 
    console.log("📦 Window loaded, initializing chatbot...");
    setTimeout(initChatbot,50);
  });
}
</script>

</body>
</html>
