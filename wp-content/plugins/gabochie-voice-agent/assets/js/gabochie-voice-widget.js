"use strict";(()=>{(!globalThis.EventTarget||!globalThis.Event)&&console.error(`
  PartySocket requires a global 'EventTarget' class to be available!
  You can polyfill this global by adding this to your code before any partysocket imports: 
  
  \`\`\`
  import 'partysocket/event-target-polyfill';
  \`\`\`
  Please file an issue at https://github.com/partykit/partykit if you're still having trouble.
`);var k=class extends Event{message;error;constructor(e,t){super("error",t),this.message=e.message,this.error=e}},S=class extends Event{code;reason;wasClean=!0;constructor(e=1e3,t="",s){super("close",s),this.code=e,this.reason=t}},m={Event,ErrorEvent:k,CloseEvent:S};function F(e,t){if(!e)throw new Error(t)}function W(e){return new e.constructor(e.type,e)}function U(e){return"data"in e?new MessageEvent(e.type,e):"code"in e||"reason"in e?new S(e.code||1999,e.reason||"unknown reason",e):"error"in e?new k(e.error,e):new Event(e.type,e)}var V=typeof process<"u"&&typeof process.versions?.node<"u",G=typeof navigator<"u"&&navigator.product==="ReactNative",g=V||G?U:W,d={maxReconnectionDelay:1e4,minReconnectionDelay:3e3,minUptime:5e3,reconnectionDelayGrowFactor:1.3,connectionTimeout:4e3,maxRetries:Number.POSITIVE_INFINITY,maxEnqueuedMessages:Number.POSITIVE_INFINITY,startClosed:!1,debug:!1},E=!1;function z(){}var T=class u extends EventTarget{_ws;_retryCount=-1;_uptimeTimeout;_connectTimeout;_shouldReconnect=!0;_connectLock=!1;_binaryType="blob";_closeCalled=!1;_didWarnAboutClosedSend=!1;_messageQueue=[];_debugLogger=console.log.bind(console);_url;_protocols;_options;constructor(t,s,i={}){super(),this._url=t,this._protocols=s,this._options=i,this._options.startClosed&&(this._shouldReconnect=!1),this._options.debugLogger&&(this._debugLogger=this._options.debugLogger),this._connect()}static get CONNECTING(){return 0}static get OPEN(){return 1}static get CLOSING(){return 2}static get CLOSED(){return 3}get CONNECTING(){return u.CONNECTING}get OPEN(){return u.OPEN}get CLOSING(){return u.CLOSING}get CLOSED(){return u.CLOSED}get binaryType(){return this._ws?this._ws.binaryType:this._binaryType}set binaryType(t){this._binaryType=t,this._ws&&(this._ws.binaryType=t)}get retryCount(){return Math.max(this._retryCount,0)}get bufferedAmount(){return this._messageQueue.reduce((t,s)=>(typeof s=="string"?t+=s.length:s instanceof Blob?t+=s.size:t+=s.byteLength,t),0)+(this._ws?this._ws.bufferedAmount:0)}get extensions(){return this._ws?this._ws.extensions:""}get protocol(){return this._ws?this._ws.protocol:""}get readyState(){return this._closeCalled?u.CLOSED:this._ws?this._ws.readyState:this._options.startClosed?u.CLOSED:u.CONNECTING}get url(){return this._ws?this._ws.url:""}get shouldReconnect(){return this._shouldReconnect}onclose=null;onerror=null;onmessage=null;onopen=null;close(t=1e3,s){if(this._closeCalled=!0,this._shouldReconnect=!1,this._clearTimeouts(),!this._ws){this._debug("close enqueued: no ws instance");return}if(this._ws.readyState===this.CLOSED||this._ws.readyState===this.CLOSING){this._debug("close: already closing or closed");return}this._disconnect(t,s)}reconnect(t,s){this._shouldReconnect=!0,this._closeCalled=!1,this._didWarnAboutClosedSend=!1,this._retryCount=-1,!this._ws||this._ws.readyState===this.CLOSED||this._ws.readyState===this.CLOSING?this._connect():(this._disconnect(t,s),this._connect())}send(t){if(this._ws&&this._ws.readyState===this.OPEN)return this._debug("send",t),this._ws.send(t),!0;this._closeCalled&&!this._didWarnAboutClosedSend&&(this._didWarnAboutClosedSend=!0,console.warn("ReconnectingWebSocket: send() was called after close(). The message has been buffered, but it will only be delivered if reconnect() is called on this socket. If this socket has been discarded, the message is lost \u2014 this usually means a stale socket reference is being used."));let{maxEnqueuedMessages:s=d.maxEnqueuedMessages}=this._options;return this._messageQueue.length<s&&(this._debug("enqueue",t),this._messageQueue.push(t)),!1}drainQueuedMessages(){let t=this._messageQueue;return this._messageQueue=[],t}_debug(...t){this._options.debug&&this._debugLogger("RWS>",...t)}_getNextDelay(){let{reconnectionDelayGrowFactor:t=d.reconnectionDelayGrowFactor,minReconnectionDelay:s=d.minReconnectionDelay,maxReconnectionDelay:i=d.maxReconnectionDelay}=this._options,n=0;return this._retryCount>0&&(n=s*t**(this._retryCount-1),n>i&&(n=i)),this._debug("next delay",n),n}_wait(){return new Promise(t=>{setTimeout(t,this._getNextDelay())})}_getNextProtocols(t){if(!t)return Promise.resolve(null);if(typeof t=="string"||Array.isArray(t))return Promise.resolve(t);if(typeof t=="function"){let s=t();if(!s)return Promise.resolve(null);if(typeof s=="string"||Array.isArray(s))return Promise.resolve(s);if(s.then)return s}throw Error("Invalid protocols")}_getNextUrl(t){if(typeof t=="string")return Promise.resolve(t);if(typeof t=="function"){let s=t();if(typeof s=="string")return Promise.resolve(s);if(s.then)return s}throw Error("Invalid URL")}_connect(){if(this._connectLock||!this._shouldReconnect)return;this._connectLock=!0;let{maxRetries:t=d.maxRetries,connectionTimeout:s=d.connectionTimeout}=this._options;if(this._retryCount>=t){this._debug("max retries reached",this._retryCount,">=",t),this._connectLock=!1;return}this._retryCount++,this._debug("connect",this._retryCount),this._removeListeners(),this._wait().then(()=>Promise.all([this._getNextUrl(this._url),this._getNextProtocols(this._protocols||null)])).then(([i,n])=>{if(this._closeCalled){this._connectLock=!1;return}!this._options.WebSocket&&typeof WebSocket>"u"&&!E&&(console.error(`\u203C\uFE0F No WebSocket implementation available. You should define options.WebSocket. 

For example, if you're using node.js, run \`npm install ws\`, and then in your code:

import PartySocket from 'partysocket';
import WS from 'ws';

const partysocket = new PartySocket({
  host: "127.0.0.1:1999",
  room: "test-room",
  WebSocket: WS
});

`),E=!0);let r=this._options.WebSocket||WebSocket;this._debug("connect",{url:i,protocols:n}),this._ws=n?new r(i,n):new r(i),this._ws.binaryType=this._binaryType,this._connectLock=!1,this._addListeners(),this._connectTimeout=setTimeout(()=>this._handleTimeout(),s)}).catch(i=>{this._connectLock=!1,this._handleError(new m.ErrorEvent(Error(i.message),this))})}_handleTimeout(){this._debug("timeout event"),this._handleError(new m.ErrorEvent(Error("TIMEOUT"),this))}_disconnect(t=1e3,s){if(this._clearTimeouts(),!!this._ws){this._removeListeners();try{(this._ws.readyState===this.OPEN||this._ws.readyState===this.CONNECTING)&&this._ws.close(t,s),this._handleClose(new m.CloseEvent(t,s,this))}catch{}}}_acceptOpen(){this._debug("accept open"),this._retryCount=0}_handleOpen=t=>{this._debug("open event");let{minUptime:s=d.minUptime}=this._options;clearTimeout(this._connectTimeout),this._uptimeTimeout=setTimeout(()=>this._acceptOpen(),s),F(this._ws,"WebSocket is not defined"),this._ws.binaryType=this._binaryType,this._messageQueue.forEach(i=>{this._ws?.send(i)}),this._messageQueue=[],this.onopen&&this.onopen(t),this.dispatchEvent(g(t))};_handleMessage=t=>{this._debug("message event"),this.onmessage&&this.onmessage(t),this.dispatchEvent(g(t))};_handleError=t=>{this._debug("error event",t.message),this._disconnect(void 0,t.message==="TIMEOUT"?"timeout":void 0),this.onerror&&this.onerror(t),this._debug("exec error listeners"),this.dispatchEvent(g(t)),this._connect()};_handleClose=t=>{this._debug("close event"),this._clearTimeouts(),this._options.shouldReconnectOnClose&&!this._options.shouldReconnectOnClose(t)&&(this._shouldReconnect=!1),this._shouldReconnect&&this._connect(),this.onclose&&this.onclose(t),this.dispatchEvent(g(t))};_removeListeners(){this._ws&&(this._debug("removeListeners"),this._ws.removeEventListener("open",this._handleOpen),this._ws.removeEventListener("close",this._handleClose),this._ws.removeEventListener("message",this._handleMessage),this._ws.removeEventListener("error",this._handleError),this._ws.addEventListener("error",z))}_addListeners(){this._ws&&(this._debug("addListeners"),this._ws.addEventListener("open",this._handleOpen),this._ws.addEventListener("close",this._handleClose),this._ws.addEventListener("message",this._handleMessage),this._ws.addEventListener("error",this._handleError))}_clearTimeouts(){clearTimeout(this._connectTimeout),clearTimeout(this._uptimeTimeout)}};var $=e=>e[1]!==null&&e[1]!==void 0;function q(){if(crypto?.randomUUID)return crypto.randomUUID();let e=Date.now(),t=performance?.now&&performance.now()*1e3||0;return"xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g,function(s){let i=Math.random()*16;return e>0?(i=(e+i)%16|0,e=Math.floor(e/16)):(i=(t+i)%16|0,t=Math.floor(t/16)),(s==="x"?i:i&3|8).toString(16)})}function P(e,t,s={}){let{host:i,path:n,protocol:r,room:h,party:a,basePath:f,prefix:c,query:p}=e,o=i.replace(/^(http|https|ws|wss):\/\//,"");if(o.endsWith("/")&&(o=o.slice(0,-1)),n?.startsWith("/"))throw new Error("path must not start with a slash");let y=a??"main",w=n?`/${n}`:"",v=r||(o.startsWith("localhost:")||o.startsWith("127.0.0.1:")||o.startsWith("192.168.")||o.startsWith("10.")||o.startsWith("172.")&&o.split(".")[1]>="16"&&o.split(".")[1]<="31"||o.startsWith("[::ffff:7f00:1]:")?t:`${t}s`),x=`${v}://${o}/${f||`${c||"parties"}/${y}/${h}`}${w}`,C=(B={})=>`${x}?${new URLSearchParams([...Object.entries(s),...Object.entries(B).filter($)])}`,A=typeof p=="function"?async()=>C(await p()):C(p);return{host:o,path:w,room:h,name:y,protocol:v,partyUrl:x,urlProvider:A}}var O=class extends T{_pk;_pkurl;name;room;host;path;basePath;constructor(e){let t=L(e);if(super(t.urlProvider,t.protocols,t.socketOptions),this.partySocketOptions=e,this.setWSProperties(t),!e.startClosed&&!this.room&&!this.basePath)throw this.close(),new Error("Either room or basePath must be provided to connect. Use startClosed: true to create a socket and set them via updateProperties before calling reconnect().");e.disableNameValidation||(e.party?.includes("/")&&console.warn(`PartySocket: party name "${e.party}" contains forward slash which may cause routing issues. Consider using a name without forward slashes or set disableNameValidation: true to bypass this warning.`),e.room?.includes("/")&&console.warn(`PartySocket: room name "${e.room}" contains forward slash which may cause routing issues. Consider using a name without forward slashes or set disableNameValidation: true to bypass this warning.`))}updateProperties(e){let t=L({...this.partySocketOptions,...e,host:e.host??this.host,room:e.room??this.room,path:e.path??this.path,basePath:e.basePath??this.basePath});this._url=t.urlProvider,this._protocols=t.protocols,this._options=t.socketOptions,this.setWSProperties(t)}setWSProperties(e){let{_pk:t,_pkurl:s,name:i,room:n,host:r,path:h,basePath:a}=e;this._pk=t,this._pkurl=s,this.name=i,this.room=n,this.host=r,this.path=h,this.basePath=a}reconnect(e,t){if(!this.host)throw new Error("The host must be set before connecting, use `updateProperties` method to set it or pass it to the constructor.");if(!this.room&&!this.basePath)throw new Error("The room (or basePath) must be set before connecting, use `updateProperties` method to set it or pass it to the constructor.");super.reconnect(e,t)}get id(){return this._pk}get roomUrl(){return this._pkurl}static async fetch(e,t){let s=P(e,"http"),i=typeof s.urlProvider=="string"?s.urlProvider:await s.urlProvider();return(e.fetch??fetch)(i,t)}};function L(e){let{id:t,host:s,path:i,party:n,room:r,protocol:h,query:a,protocols:f,...c}=e,p=t||q(),o=P(e,"ws",{_pk:p});return{_pk:p,_pkurl:o.partyUrl,name:o.name,room:o.room,host:o.host,path:o.path,basePath:e.basePath,protocols:f,socketOptions:c,urlProvider:o.urlProvider}}function J(e){if(e===e.toUpperCase()&&e!==e.toLowerCase())return e.toLowerCase().replace(/_/g,"-");let t=e.replace(/[A-Z]/g,s=>`-${s.toLowerCase()}`);return t=t.startsWith("-")?t.slice(1):t,t.replace(/_/g,"-").replace(/-$/,"")}var N="Audio output device selection is not supported in this browser.",D="Could not switch audio output device.",j=`
class AudioCaptureProcessor extends AudioWorkletProcessor {
  constructor() {
    super();
    this.buffer = [];
    this.sampleRate = sampleRate;
    this.targetRate = 16000;
    this.ratio = this.sampleRate / this.targetRate;
  }

  process(inputs) {
    const input = inputs[0];
    if (!input || !input[0]) return true;

    const channelData = input[0];

    // Linear interpolation resampling (e.g. 48kHz \u2192 16kHz).
    // Nearest-neighbor (picking every Nth sample) introduces aliasing
    // artifacts, especially on sibilants (s, f, th). Linear interpolation
    // blends adjacent samples, acting as a basic low-pass filter.
    for (let i = 0; i < channelData.length; i += this.ratio) {
      const idx = Math.floor(i);
      const frac = i - idx;
      if (idx + 1 < channelData.length) {
        this.buffer.push(channelData[idx] * (1 - frac) + channelData[idx + 1] * frac);
      } else if (idx < channelData.length) {
        this.buffer.push(channelData[idx]);
      }
    }

    if (this.buffer.length >= 1600) {
      const chunk = new Float32Array(this.buffer);
      this.port.postMessage({ type: 'audio', samples: chunk }, [chunk.buffer]);
      this.buffer = [];
    }

    return true;
  }
}

registerProcessor('audio-capture-processor', AudioCaptureProcessor);
`;function H(e){let t=new ArrayBuffer(e.length*2),s=new DataView(t);for(let i=0;i<e.length;i++){let n=Math.max(-1,Math.min(1,e[i]));s.setInt16(i*2,n<0?n*32768:n*32767,!0)}return t}function Q(e){let t=0;for(let s=0;s<e.length;s++)t+=e[s]*e[s];return Math.sqrt(t/e.length)}var Y=class{#i;#e;constructor(e){this.#i=null,this.onopen=null,this.onclose=null,this.onerror=null,this.onmessage=null,this.#e=e}get connected(){return this.#i?.readyState===WebSocket.OPEN}sendJSON(e){this.#i?.readyState===WebSocket.OPEN&&this.#i.send(JSON.stringify(e))}sendBinary(e){this.#i?.readyState===WebSocket.OPEN&&this.#i.send(e)}connect(){if(this.#i)return;let e=new O({party:J(this.#e.agent),room:this.#e.name??"default",host:this.#e.host??window.location.host,prefix:"agents",query:this.#e.query});e.onopen=()=>this.onopen?.(),e.onclose=()=>this.onclose?.(),e.onerror=()=>this.onerror?.(),e.onmessage=t=>{this.onmessage?.(t.data)},this.#i=e}disconnect(){this.#i?.close(),this.#i=null}},I=class{#i="idle";#e=[];#p=null;#c=0;#o=!1;#k=!1;#r=null;#v=null;#H=null;#M=null;#A=16e3;#S=null;#Q=null;#u=!1;#T=0;#d=!1;#Y;#K;#Z;#X;#B;#s=null;#n;#h=null;#F=!1;#W=null;#U=null;#a=null;#f=!1;#g=[];#_=!1;#x=!1;#m=new Set;#L=0;#P=null;#l=null;#O=null;#y=null;#V=!1;#C;#w=0;#G=0;#b=0;#E=0;#N=new Map;constructor(e){this.#n=e,this.#Y=e.silenceThreshold??.04,this.#K=e.silenceDurationMs??500,this.#Z=e.interruptThreshold??.05,this.#X=e.interruptChunks??2,this.#B=e.maxTranscriptMessages??200,this.#C=e.outputDeviceId??"default"}get status(){return this.#i}get transcript(){return this.#e}get metrics(){return this.#p}get audioLevel(){return this.#c}get isMuted(){return this.#o}get connected(){return this.#k}get error(){return this.#r}get outputDeviceError(){return this.#v}get interimTranscript(){return this.#S}get serverProtocolVersion(){return this.#Q}addEventListener(e,t){let s=this.#N.get(e);s||(s=new Set,this.#N.set(e,s)),s.add(t)}removeEventListener(e,t){this.#N.get(e)?.delete(t)}#t(e,t){let s=this.#N.get(e);if(s)for(let i of s)i(t)}#tt(){this.#e.length>this.#B&&(this.#e=this.#e.slice(-this.#B))}#D(e){this.#v!==e&&(this.#v=e,this.#t("outputdeviceerror",e))}connect(){if(this.#s)return;let e=this.#n.transport??new Y({agent:this.#n.agent,name:this.#n.name,host:this.#n.host,query:this.#n.query});e.onopen=()=>{this.#k=!0,this.#r=null,e.sendJSON({type:"hello",protocol_version:1}),this.#t("connectionchange",!0),this.#t("error",null),this.#u&&(this.#d=!1,e.sendJSON({type:"start_call"}))},e.onclose=()=>{this.#k=!1,this.#t("connectionchange",!1)},e.onerror=()=>{this.#r="Connection lost. Reconnecting...",this.#t("error",this.#r)},e.onmessage=t=>{typeof t=="string"?this.#at(t):t instanceof Blob?t.arrayBuffer().then(s=>{this.#g.push(s),this.#it()}):t instanceof ArrayBuffer&&(this.#g.push(t),this.#it())},this.#s=e,e.connect()}disconnect(){this.endCall(),this.#s?.disconnect(),this.#s=null,this.#k=!1,this.#t("connectionchange",!1)}async startCall(){if(!this.#s?.connected){this.#r="Cannot start call: not connected. Call connect() first.",this.#t("error",this.#r);return}if(this.#u)return;let e=++this.#T;this.#u=!0,this.#d=!1,this.#r=null,this.#p=null,this.#t("error",null),this.#t("metricschange",null);let t={type:"start_call"};this.#n.preferredFormat&&(t.preferred_format=this.#n.preferredFormat),this.#s.sendJSON(t);let s=await this.#q();this.#z(e)||(await this.#et(s),!this.#z(e)&&(this.#n.audioInput?(this.#n.audioInput.onAudioLevel=i=>this.#nt(i),this.#n.audioInput.onAudioData=i=>{this.#s?.connected&&!this.#o&&this.#s.sendBinary(i)},await this.#n.audioInput.start()):await this.#ut(),this.#z(e)))}endCall(){this.#T++,this.#u=!1,this.#d=!1,this.#s?.connected&&this.#s.sendJSON({type:"end_call"}),this.#$(),this.#i="idle",this.#t("statuschange","idle")}#ot(e){return this.#u&&this.#T===e}#z(e){return this.#ot(e)?!1:(this.#u||this.#$(),!0)}#$(){this.#n.audioInput?(this.#n.audioInput.stop(),this.#n.audioInput.onAudioLevel=null,this.#n.audioInput.onAudioData=null):this.#dt(),this.#R(),this.#ht(),this.#rt()}toggleMute(){this.#o=!this.#o,this.#o&&(this.#c=0,this.#t("audiolevelchange",0)),this.#o&&this.#f&&(this.#f=!1,this.#a&&(clearTimeout(this.#a),this.#a=null),this.#s?.connected&&this.#s.sendJSON({type:"end_of_speech"})),this.#t("mutechange",this.#o)}sendText(e){this.#s?.connected&&this.#s.sendJSON({type:"text_message",text:e})}sendJSON(e){this.#s?.connected&&this.#s.sendJSON(e)}async setOutputDevice(e){this.#C=e??"default";let t=++this.#w;this.#l&&await this.#I(this.#l,t)}get lastCustomMessage(){return this.#H}get audioFormat(){return this.#M}get sampleRate(){return this.#A}#at(e){let t;try{t=JSON.parse(e)}catch{return}switch(t.type){case"welcome":this.#Q=t.protocol_version,t.protocol_version!==1&&console.warn(`[VoiceClient] Protocol version mismatch: client=1, server=${t.protocol_version}`);break;case"audio_config":this.#d=!0,this.#M=t.format,this.#A=typeof t.sampleRate=="number"&&t.sampleRate>0?t.sampleRate:16e3;break;case"status":if(this.#i=t.status,t.status==="idle"&&this.#u){if(!(this.#d||this.#r!==null)){this.#t("statuschange",this.#i);break}this.#T++,this.#u=!1,this.#d=!1,this.#$()}t.status==="listening"?(this.#d=!0,this.#r=null,this.#t("error",null)):(t.status==="thinking"||t.status==="speaking")&&(this.#d=!0),this.#t("statuschange",this.#i);break;case"transcript_interim":this.#S=t.text,this.#t("interimtranscript",this.#S);break;case"playback_interrupt":this.#R();break;case"transcript":this.#S=null,this.#t("interimtranscript",null),t.role==="user"&&this.#_&&this.#R(),this.#e=[...this.#e,{role:t.role,text:t.text,timestamp:Date.now()}],this.#tt(),this.#t("transcriptchange",this.#e);break;case"transcript_start":this.#e=[...this.#e,{role:"assistant",text:"",timestamp:Date.now()}],this.#tt(),this.#t("transcriptchange",this.#e);break;case"transcript_delta":{if(this.#e.length===0)break;let s=[...this.#e],i=s[s.length-1];i.role==="assistant"&&(s[s.length-1]={...i,text:i.text+t.text},this.#e=s,this.#t("transcriptchange",this.#e));break}case"transcript_end":{if(this.#e.length===0)break;let s=[...this.#e],i=s[s.length-1];i.role==="assistant"&&(s[s.length-1]={...i,text:t.text},this.#e=s,this.#t("transcriptchange",this.#e));break}case"metrics":this.#p={llm_ms:t.llm_ms,tts_ms:t.tts_ms,first_audio_ms:t.first_audio_ms,total_ms:t.total_ms},this.#t("metricschange",this.#p);break;case"error":this.#r=t.message,this.#t("error",this.#r);break;default:this.#H=t,this.#t("custommessage",t);break}}async#q(){return this.#h||(this.#h=new AudioContext({sampleRate:48e3})),this.#h.state==="suspended"&&await this.#h.resume(),this.#h}#ht(){this.#h&&(this.#J(),this.#h.close().catch(()=>{}),this.#h=null,this.#F=!1)}async#et(e){if(this.#y)return this.#y;if(this.#O)return this.#O;if(this.#V)return e.destination;let t=this.#G,s=this.#lt(e,t);this.#y=s;try{return await s}finally{this.#y===s&&(this.#y=null)}}async#lt(e,t){try{let s=e.createMediaStreamDestination(),i=new Audio;return i.autoplay=!0,i.srcObject=s.stream,this.#l=i,this.#O=s,await this.#I(i,this.#w),this.#st(i,t)?(await i.play(),this.#st(i,t)?s:(this.#j(i),e.destination)):(this.#j(i),e.destination)}catch(s){return console.warn("[VoiceClient] HTMLAudioElement playback output unavailable; using default AudioContext destination.",s),this.#J(),this.#V=!0,e.destination}}#st(e,t){return this.#l===e&&this.#G===t}async#I(e,t){let s=this.#C,i=e.setSinkId;if(!i){if(s==="default"){this.#D(null);return}this.#D(N);return}try{if(await i.call(e,s),t!==this.#w||s!==this.#C){this.#l===e&&await this.#I(e,this.#w);return}(this.#v===N||this.#v===D)&&this.#D(null)}catch{if(t!==this.#w||s!==this.#C){this.#l===e&&await this.#I(e,this.#w);return}this.#D(D)}}#J(){this.#G++,this.#l&&(this.#j(this.#l),this.#l=null),this.#O=null,this.#y=null,this.#V=!1}#j(e){e.pause(),e.srcObject=null}async#ct(e,t){try{let s=await this.#q(),i;if(this.#M==="pcm16"){let a=new Int16Array(e);i=s.createBuffer(1,a.length,this.#A);let f=i.getChannelData(0);for(let c=0;c<a.length;c++)f[c]=a[c]/32768}else i=await s.decodeAudioData(e.slice(0));if(t!==this.#b)return;this.#l&&this.#m.size===0&&this.#P!==null&&s.currentTime-this.#P>.3&&this.#J();let n=await this.#et(s);if(t!==this.#b)return;let r=s.createBufferSource();r.buffer=i,r.connect(n),this.#m.add(r),r.onended=()=>{this.#m.delete(r),t===this.#b&&!this.#x&&this.#m.size===0&&this.#g.length===0&&(this.#_=!1)};let h=Math.max(s.currentTime,this.#L);this.#L=h+i.duration,this.#P=this.#L,r.start(h)}catch(s){console.error("[VoiceClient] Audio playback error:",s)}}async#it(){if(this.#x||this.#g.length===0)return;this.#x=!0,this.#_=!0;let e=this.#b;for(;e===this.#b&&this.#g.length>0;){let t=this.#g.shift();await this.#ct(t,e)}e===this.#b&&(this.#x=!1,this.#m.size===0&&(this.#_=!1))}#R(){this.#b++;let e=[...this.#m];this.#m.clear();for(let t of e)try{t.stop()}catch{}this.#g=[],this.#_=!1,this.#x=!1,this.#L=0,this.#P=this.#h?this.#h.currentTime:null}async#ut(){try{let e=await navigator.mediaDevices.getUserMedia({audio:{sampleRate:{ideal:48e3},channelCount:1,echoCancellation:!0,noiseSuppression:!0,autoGainControl:!0}});this.#U=e;let t=await this.#q();if(!this.#F){let n=new Blob([j],{type:"application/javascript"}),r=URL.createObjectURL(n);await t.audioWorklet.addModule(r),URL.revokeObjectURL(r),this.#F=!0}let s=t.createMediaStreamSource(e),i=new AudioWorkletNode(t,"audio-capture-processor");this.#W=i,i.port.onmessage=n=>{if(n.data.type==="audio"&&!this.#o){let r=n.data.samples,h=Q(r),a=H(r);this.#s?.connected&&this.#s.sendBinary(a),this.#nt(h)}},s.connect(i),i.connect(t.destination)}catch(e){console.error("[VoiceClient] Mic error:",e),this.#r="Microphone access denied. Please allow microphone access and try again.",this.#t("error",this.#r)}}#dt(){this.#W?.disconnect(),this.#W=null,this.#U?.getTracks().forEach(e=>e.stop()),this.#U=null,this.#rt()}#nt(e){this.#o||(this.#c=e,this.#t("audiolevelchange",e),this.#_&&e>this.#Z?(this.#E++,this.#E>=this.#X&&(this.#R(),this.#E=0,this.#s?.connected&&this.#s.sendJSON({type:"interrupt"}))):this.#E=0,e>this.#Y?(this.#f||(this.#f=!0,this.#s?.connected&&this.#s.sendJSON({type:"start_of_speech"})),this.#a&&(clearTimeout(this.#a),this.#a=null)):this.#f&&(this.#a||(this.#a=setTimeout(()=>{this.#f=!1,this.#a=null,this.#s?.connected&&this.#s.sendJSON({type:"end_of_speech"})},this.#K))))}#rt(){this.#a&&(clearTimeout(this.#a),this.#a=null),this.#f=!1,this.#E=0,this.#c=0,this.#t("audiolevelchange",0)}};var b=Object.assign({agent:"GabochieVoiceAgent",host:window.location.host,siteName:"Gabochie Design"},window.GabochieVoiceAgent||{}),R="gabochie_voice_session",K={idle:"Ready",listening:"Listening\u2026",thinking:"Thinking\u2026",speaking:"Speaking\u2026"};function Z(){try{let e=sessionStorage.getItem(R);return e||(e=crypto.randomUUID(),sessionStorage.setItem(R,e)),e}catch{return crypto.randomUUID()}}var l={mic:'<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.91-3c-.49 0-.9.36-.98.85C16.52 14.2 14.47 16 12 16s-4.52-1.8-4.93-4.15c-.08-.49-.49-.85-.98-.85-.61 0-1.09.54-1 1.14.49 3 2.89 5.35 5.91 5.78V20c0 .55.45 1 1 1s1-.45 1-1v-2.08c3.02-.43 5.42-2.78 5.91-5.78.1-.6-.39-1.14-1-1.14z"/></svg>',phone:'<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',mute:'<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M19 11h-1.7c0 .74-.16 1.43-.43 2.05l1.23 1.23c.56-.98.9-2.09.9-3.28zm-4.02.17c0-.06.02-.11.02-.17V5c0-1.66-1.34-3-3-3S9 3.34 9 5v.18l5.98 5.99zM4.27 3 3 4.27l6.01 6.01V11c0 1.66 1.33 3 2.99 3 .22 0 .44-.03.65-.08l1.66 1.66c-.71.33-1.5.52-2.31.52-2.76 0-5.3-2.1-5.3-5.1H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c.91-.13 1.77-.45 2.54-.9L19.73 21 21 19.73 4.27 3z"/></svg>',unmute:'<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>',end:'<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M12 9c-1.6 0-3.15.25-4.6.72v3.1c0 .39-.23.74-.56.9-.98.49-1.87 1.12-2.66 1.85-.18.18-.43.28-.7.28-.28 0-.53-.11-.71-.29L.29 13.08c-.18-.17-.29-.42-.29-.7 0-.28.11-.53.29-.71C3.34 8.78 7.46 7 12 7s8.66 1.78 11.71 4.67c.18.18.29.43.29.71 0 .28-.11.53-.29.7l-2.48 2.48c-.18.18-.43.29-.71.29-.27 0-.52-.11-.7-.28-.79-.74-1.69-1.36-2.67-1.85-.33-.16-.56-.5-.56-.9v-3.1C15.15 9.25 13.6 9 12 9z"/></svg>',close:'<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'},X=`
<style>
  :host { all: initial; }
  * { box-sizing: border-box; }
  .fab {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 999999;
    width: 56px;
    height: 56px;
    border: none;
    border-radius: 50%;
    background: #072E28;
    color: #C9A96E;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(7, 46, 40, 0.45);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }
  .fab:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(7, 46, 40, 0.55); }
  .fab:active { transform: translateY(0); }
  .panel {
    position: fixed;
    right: 20px;
    bottom: 88px;
    z-index: 999999;
    width: 360px;
    max-width: calc(100vw - 32px);
    height: 520px;
    max-height: calc(100vh - 120px);
    display: flex;
    flex-direction: column;
    background: #FAF7F2;
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(28, 43, 40, 0.3);
    overflow: hidden;
    font-family: "Jost", "Segoe UI", system-ui, -apple-system, sans-serif;
    color: #1C2B28;
  }
  .panel[hidden] { display: none; }
  .header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #0D5C52;
    color: #FDFCF9;
  }
  .avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #C9A96E;
    color: #072E28;
    font-family: "Playfair Display", Georgia, serif;
    font-weight: 700;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .titles { flex: 1; min-width: 0; }
  .site-name {
    font-family: "Playfair Display", Georgia, serif;
    font-size: 15px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .status { display: flex; align-items: center; gap: 6px; font-size: 12px; opacity: 0.9; margin-top: 2px; }
  .dot { width: 8px; height: 8px; border-radius: 50%; background: #E8D5A8; }
  .dot.active { background: #7FD8A9; box-shadow: 0 0 6px #7FD8A9; }
  .icon-btn {
    border: none;
    background: transparent;
    color: #FDFCF9;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }
  .icon-btn:hover { background: rgba(255, 255, 255, 0.15); }
  .transcript {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #F2FAF8;
  }
  .bubble {
    max-width: 85%;
    padding: 10px 13px;
    border-radius: 14px;
    font-size: 14px;
    line-height: 1.45;
    word-wrap: break-word;
    white-space: pre-wrap;
  }
  .bubble.user {
    align-self: flex-end;
    background: #072E28;
    color: #FDFCF9;
    border-bottom-right-radius: 4px;
  }
  .bubble.assistant {
    align-self: flex-start;
    background: #FDFCF9;
    color: #1C2B28;
    border-left: 3px solid #C9A96E;
    border-bottom-left-radius: 4px;
  }
  .bubble.interim {
    align-self: flex-end;
    background: #E8F4F2;
    color: #0A4840;
    font-style: italic;
    opacity: 0.85;
  }
  .controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    padding: 12px;
    background: #FAF7F2;
    border-top: 1px solid #E8F4F2;
  }
  .ctrl {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 50%;
    background: #E8F4F2;
    color: #0D5C52;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  .ctrl:hover { background: #E8D5A8; }
  .ctrl.end { background: #C0392B; color: #FDFCF9; }
  .ctrl.end:hover { background: #A93226; }
  .ctrl.active { background: #0D5C52; color: #FDFCF9; }
  .error {
    padding: 8px 14px;
    font-size: 12px;
    color: #C0392B;
    background: #FDECEA;
    border-top: 1px solid #F5C6C0;
  }
  .error[hidden] { display: none; }
</style>
<button class="fab" id="fab" aria-label="Talk to Gabochie" title="Talk to Gabochie">${l.mic}</button>
<div class="panel" id="panel" hidden>
  <div class="header">
    <div class="avatar">G</div>
    <div class="titles">
      <div class="site-name"></div>
      <div class="status"><span class="dot" id="dot"></span><span id="status-label">Ready</span></div>
    </div>
    <button class="icon-btn" id="close" aria-label="Close" title="Close">${l.close}</button>
  </div>
  <div class="transcript" id="transcript"></div>
  <div class="controls">
    <button class="ctrl" id="mute" aria-label="Mute" title="Mute">${l.mute}</button>
    <button class="ctrl end" id="end" aria-label="End call" title="End call">${l.end}</button>
    <button class="ctrl" id="call" aria-label="Call again" title="Call again" hidden>${l.phone}</button>
  </div>
  <div class="error" id="error" hidden></div>
</div>
`,_=class{constructor(){this.client=null,this.muted=!1,this.messages=[],this.ended=!1,this.panelOpen=!1;let t=document.getElementById("gabochie-voice-widget")||document.body;this.host=document.createElement("div"),this.host.id="gabochie-voice-widget-host";let s=this.host.attachShadow({mode:"open"});s.innerHTML=X,t.appendChild(this.host),this.shadow=s,this.fab=s.getElementById("fab"),this.panel=s.getElementById("panel"),this.dot=s.getElementById("dot"),this.statusLabel=s.getElementById("status-label"),this.siteName=s.querySelector(".site-name"),this.transcriptEl=s.getElementById("transcript"),this.muteBtn=s.getElementById("mute"),this.endBtn=s.getElementById("end"),this.callBtn=s.getElementById("call"),this.closeBtn=s.getElementById("close"),this.errorEl=s.getElementById("error"),this.siteName.textContent=b.siteName,this.fab.addEventListener("click",()=>{this.togglePanel()}),this.closeBtn.addEventListener("click",()=>this.closePanel()),this.endBtn.addEventListener("click",()=>this.endCall()),this.callBtn.addEventListener("click",()=>{this.startCall()}),this.muteBtn.addEventListener("click",()=>this.toggleMute())}async togglePanel(){if(this.panelOpen){this.closePanel();return}this.panel.hidden=!1,this.panelOpen=!0,await this.startCall()}closePanel(){this.panel.hidden=!0,this.panelOpen=!1,this.client?.endCall()}async startCall(){let t=this.#i();try{await this.#e(t),await t.startCall(),this.ended=!1,this.callBtn.hidden=!0,this.endBtn.hidden=!1,this.errorEl.hidden=!0,this.#c()}catch(s){this.#o(s&&s.message?s.message:String(s))}}endCall(){this.client?.endCall(),this.ended=!0,this.callBtn.hidden=!1,this.endBtn.hidden=!0,this.#p("idle")}toggleMute(){this.client&&(this.client.toggleMute(),this.muted=!this.muted,this.muteBtn.innerHTML=this.muted?l.unmute:l.mute,this.muteBtn.classList.toggle("active",this.muted),this.muteBtn.setAttribute("aria-label",this.muted?"Unmute":"Mute"),this.muteBtn.setAttribute("title",this.muted?"Unmute":"Mute"))}#i(){if(!this.client){let t=new I({agent:b.agent,name:Z(),host:b.host});t.addEventListener("statuschange",s=>this.#p(s)),t.addEventListener("transcriptchange",s=>{this.messages=s,this.#c()}),t.addEventListener("interimtranscript",s=>{this.interim=s,this.#c()}),t.addEventListener("mutechange",s=>{this.muted=s,this.muteBtn.innerHTML=s?l.unmute:l.mute,this.muteBtn.classList.toggle("active",s)}),t.addEventListener("error",s=>{s&&this.#o(s)}),this.client=t}return this.client}#e(t){return t.connected?Promise.resolve():(t.connect(),new Promise((s,i)=>{let n=setTimeout(()=>{a(),i(new Error("Could not connect to the voice service. Please try again."))},12e3),r=()=>{clearTimeout(n),a(),s()},h=()=>{t.error&&(clearTimeout(n),a(),i(new Error(t.error)))},a=()=>{t.removeEventListener("connectionchange",r),t.removeEventListener("error",h)};t.addEventListener("connectionchange",r),t.addEventListener("error",h)}))}#p(t){let s=K[t]||t;this.statusLabel.textContent=s,this.dot.classList.toggle("active",t!=="idle")}#c(){let t=document.createDocumentFragment();for(let s of this.messages){if(!s||typeof s.text!="string"||!s.text.trim())continue;let i=document.createElement("div");i.className=`bubble ${s.role==="user"?"user":"assistant"}`,i.textContent=s.text,t.appendChild(i)}if(this.interim){let s=document.createElement("div");s.className="bubble interim",s.textContent=this.interim,t.appendChild(s)}this.transcriptEl.replaceChildren(t),this.transcriptEl.scrollTop=this.transcriptEl.scrollHeight}#o(t){this.errorEl.textContent=t,this.errorEl.hidden=!1}};function M(){document.getElementById("gabochie-voice-widget-host")||new _}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",M,{once:!0}):M();})();
