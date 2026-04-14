/**
 * FitSense - Chat UI
 */
(function () {
    "use strict";

    var currentSessionId = null;
    var isLoadingHistory = false;
    var chatMessages   = document.getElementById("chat-messages");
    var messageInput   = document.getElementById("message-input");
    var sendBtn        = document.getElementById("send-btn");
    var limitWarning   = document.getElementById("limit-warning");
    var typingTpl      = document.getElementById("typing-tpl");
    var sessionList    = document.getElementById("session-list");
    var sidebar        = document.getElementById("sidebar");
    var sidebarOverlay = document.getElementById("sidebar-overlay");

    function init() {
        if (!chatMessages || !messageInput || !sendBtn) return;
        loadSessions();
        sendBtn.addEventListener("click", sendMessage);
        messageInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });
        messageInput.addEventListener("input", function () {
            this.style.height = "auto";
            this.style.height = Math.min(this.scrollHeight, 128) + "px";
            sendBtn.disabled = this.value.trim() === "";
        });
        document.getElementById("new-chat-btn").addEventListener("click", function () {
            if (window.innerWidth < 1024) closeSidebar();
            location.hash = "chat";
            startNewChat();
        });
        var mobileNew = document.getElementById("mobile-new-chat-btn");
        if (mobileNew) mobileNew.addEventListener("click", function() { location.hash = "chat"; startNewChat(); });
        var sidebarOpenBtn = document.getElementById("sidebar-open-btn"); if (sidebarOpenBtn) sidebarOpenBtn.addEventListener("click", openSidebar);
        sidebarOverlay.addEventListener("click", closeSidebar);
        // user-menu handled by inline script in chat.php
        attachChips(document);
    }

    function attachChips(root) {
        root.querySelectorAll(".suggestion-chip").forEach(function (btn) {
            btn.addEventListener("click", function () {
                messageInput.value = this.textContent.trim();
                messageInput.dispatchEvent(new Event("input"));
                messageInput.focus();
                sendMessage();
            });
        });
    }

    function openSidebar() {
        sidebar.classList.remove("-translate-x-full");
        sidebarOverlay.classList.remove("hidden");
        requestAnimationFrame(function () { sidebarOverlay.classList.remove("opacity-0"); });
    }
    function closeSidebar() {
        sidebar.classList.add("-translate-x-full");
        sidebarOverlay.classList.add("opacity-0");
        setTimeout(function () { sidebarOverlay.classList.add("hidden"); }, 250);
    }

    function loadSessions() {
        fetch("api/chat.php?action=sessions")
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                renderSessionList(data.sessions || []);
                if (data.sessions && data.sessions.length > 0) {
                    loadSession(data.sessions[0].session_id);
                }
            })
            .catch(console.error);
    }

    function renderSessionList(sessions) {
        if (!sessions.length) {
            sessionList.innerHTML = "<p class=\"text-zinc-600 text-xs px-3 py-4 text-center\">No conversations yet.</p>";
            return;
        }
        var html = "";
        sessions.forEach(function (s) {
            var title = s.first_message ? truncate(s.first_message, 36) : "New conversation";
            var date  = relativeDate(s.started_at);
            html += "<button data-session=\"" + s.session_id + "\" class=\"session-btn w-full text-left px-3 py-2.5 rounded-lg hover:bg-zinc-800 transition-colors group min-h-[44px]\" style=\"color:inherit\">"
                  + "<p class=\"text-sm text-zinc-300 group-hover:text-white truncate leading-snug\">" + esc(title) + "</p>"
                  + "<p class=\"text-xs text-zinc-600 mt-0.5\">" + date + "</p>"
                  + "</button>";
        });
        sessionList.innerHTML = html;
        sessionList.querySelectorAll(".session-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                if (window.innerWidth < 1024) closeSidebar();
                loadSession(parseInt(this.dataset.session));
            });
        });
        highlightSession(currentSessionId);
    }

    function highlightSession(sessionId) {
        document.querySelectorAll(".session-btn").forEach(function (btn) {
            var active = parseInt(btn.dataset.session) === sessionId;
            btn.classList.toggle("active-session", active);
            if (active) {
                btn.style.backgroundColor = "#facc15";
                btn.style.color = "#000000";
                btn.querySelectorAll("p").forEach(function(p){ p.style.color = "#000000"; });
            } else {
                btn.style.backgroundColor = "";
                btn.style.color = "";
                btn.querySelectorAll("p").forEach(function(p){ p.style.color = ""; });
            }
        });
    }

    function loadSession(sessionId) {
        currentSessionId = sessionId;
        highlightSession(sessionId);
        clearMessages();
        fetch("api/chat.php?action=history&session_id=" + sessionId)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                var msgs = data.messages || [];
                if (!msgs.length) { showEmptyState(); return; }
                hideEmptyState();
                isLoadingHistory = true;
                msgs.forEach(renderMessage);
                isLoadingHistory = false;
                scrollToBottom();
            })
            .catch(console.error);
    }

    function startNewChat() {
        fetch("api/chat.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "new_session", csrf_token: FITSENSE_CSRF })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) currentSessionId = d.session_id;
            clearMessages(); showEmptyState(); highlightSession(null);
        })
        .catch(function () { currentSessionId = null; clearMessages(); showEmptyState(); });
    }

    function sendMessage() {
        var text = messageInput.value.trim();
        if (!text) return;
        function doSend() {
            hideEmptyState();
            renderMessage({ sender_type: "member", message_type: "text", message: text });
            messageInput.value = "";
            messageInput.style.height = "auto";
            sendBtn.disabled = true;
            showTypingIndicator();
            scrollToBottom();
            fetch("api/chat.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "send_message", message: text, session_id: currentSessionId, csrf_token: FITSENSE_CSRF })
            })
            .then(function (res) {
                if (res.status === 401) { window.location.href = "login.php"; return null; }
                return res.json().then(function (d) { return { status: res.status, data: d }; });
            })
            .then(function (result) {
                if (!result) return;
                hideTypingIndicator();
                var data = result.data;
                if (result.status === 429) {
                    var msg = data.message || "Daily AI limit reached.";
                    limitWarning.textContent = msg; limitWarning.classList.remove("hidden");
                    showToast(msg, "error"); return;
                }
                if (data.success) {
                    if (data.is_recommendation) {
                        var parsed = null;
                        try { parsed = JSON.parse(data.message); } catch (e) {}
                        var title = parsed ? (parsed.title || "Recommendation") : "Recommendation";
                        var wrapper = document.createElement("div");
                        wrapper.innerHTML = renderRecommendationCard(title, data.message, "pending");
                        chatMessages.appendChild(wrapper);
                    } else {
                        renderMessage({ sender_type: "ai", message_type: "text", message: data.message });
                    }
                    scrollToBottom();
                    loadSessions();
                } else {
                    showToast(data.message || "Something went wrong.", "error");
                }
            })
            .catch(function () { hideTypingIndicator(); showToast("Connection error.", "error"); });
        }
        if (!currentSessionId) {
            fetch("api/chat.php", {
                method: "POST", headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "new_session", csrf_token: FITSENSE_CSRF })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (d.success) currentSessionId = d.session_id; doSend(); })
            .catch(function () { doSend(); });
        } else { doSend(); }
    }

    function renderMessage(msg) {
        var type = msg.sender_type, mtype = msg.message_type || "text", text = msg.message || "";
        var el = document.createElement("div");
        if (type === "member") {
            el.className = "flex justify-end";
            el.innerHTML = "<div class=\"max-w-[80%] lg:max-w-[65%]\"><div class=\"bg-yellow-400 text-black rounded-2xl rounded-br-sm px-4 py-3 text-sm break-words\">" + esc(text) + "</div></div>";
            chatMessages.appendChild(el); return;
        }
        if (type === "ai") {
            if (mtype === "recommendation") {
                var parsed = null; try { parsed = JSON.parse(text); } catch (e) {}
                var title = parsed ? (parsed.title || "Recommendation") : "Recommendation";
                el.innerHTML = renderRecommendationCard(title, text, msg.status || "pending", msg.trainer_notes || null, msg.reviewed_by_name || null);
                chatMessages.appendChild(el); return;
            }
            el.className = "flex items-start gap-3";
            el.innerHTML = "<div class=\"w-7 h-7 rounded-full bg-zinc-700 flex items-center justify-center shrink-0 mt-0.5\"><svg class=\"w-4 h-4 text-yellow-400\" fill=\"currentColor\" viewBox=\"0 0 24 24\"><path d=\"M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z\"/></svg></div>"
                + "<div class=\"bg-zinc-800 text-white rounded-2xl rounded-tl-sm px-4 py-3 text-sm break-words max-w-[80%] lg:max-w-[65%] ai-message\"></div>";
            var bubble = el.lastElementChild;
            if (isLoadingHistory) {
                bubble.innerHTML = formatMarkdown(text);
            } else {
                typewriterEffect(bubble, formatMarkdown(text), 18);
            }
            chatMessages.appendChild(el); return;
        }
        if (type === "trainer") {
            var tname = (msg.first_name && msg.last_name) ? esc(msg.first_name + " " + msg.last_name) : "Trainer";
            el.className = "flex items-start gap-3";
            el.innerHTML = "<div class=\"w-7 h-7 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-xs shrink-0 mt-0.5\">T</div>"
                + "<div class=\"max-w-[80%] lg:max-w-[65%]\"><p class=\"text-xs text-zinc-500 mb-1\">" + tname + "</p>"
                + "<div class=\"bg-zinc-700 border border-yellow-400/30 text-white rounded-2xl rounded-tl-sm px-4 py-3 text-sm break-words whitespace-pre-wrap\">" + esc(text) + "</div></div>";
            chatMessages.appendChild(el);
        }
    }

    function renderRecommendationCard(title, content, status, trainerNotes, coachName) {
        var bodyHtml = "", parsed = null;
        try { parsed = JSON.parse(content); } catch (e) {}
        if (parsed && parsed.exercises && Array.isArray(parsed.exercises)) {
            bodyHtml = "<ul class=\"list-disc list-inside space-y-1 text-sm text-zinc-200\">";
            parsed.exercises.forEach(function (ex) {
                bodyHtml += "<li><span class=\"font-medium text-white\">" + esc(ex.name || "") + "</span>";
                if (ex.sets && ex.reps) bodyHtml += " - " + esc(String(ex.sets)) + " sets x " + esc(String(ex.reps)) + " reps";
                if (ex.notes) bodyHtml += " <span class=\"text-zinc-400\">(" + esc(ex.notes) + ")</span>";
                bodyHtml += "</li>";
            });
            bodyHtml += "</ul>";
        } else if (parsed && parsed.meals && Array.isArray(parsed.meals)) {
            bodyHtml = "<ul class=\"list-disc list-inside space-y-1 text-sm text-zinc-200\">";
            parsed.meals.forEach(function (meal) {
                bodyHtml += "<li><span class=\"font-medium text-white\">" + esc(meal.name || "") + "</span>";
                if (meal.calories) bodyHtml += " - " + esc(String(meal.calories)) + " kcal";
                bodyHtml += "</li>";
            });
            bodyHtml += "</ul>";
        } else {
            bodyHtml = "<p class=\"text-sm text-zinc-200 whitespace-pre-wrap\">" + esc(content) + "</p>";
        }
        var sm = { pending: { l: "Pending Review", c: "bg-zinc-700 text-zinc-300 border-zinc-500" }, approved: { l: "Approved by Trainer", c: "bg-green-900 text-green-300 border-green-600" }, modified: { l: "Modified by Trainer", c: "bg-blue-900 text-blue-300 border-blue-600" }, rejected: { l: "Rejected", c: "bg-red-900 text-red-300 border-red-600" } };
        var b = sm[status] || sm.pending;
        return "<div class=\"border border-yellow-400/50 rounded-xl p-4 bg-zinc-900 max-w-[85%]\"><p class=\"text-xs text-yellow-300 mb-2\">AI-generated - consult a professional before following.</p><h3 class=\"text-yellow-400 font-bold text-sm mb-3\">" + esc(title) + "</h3>" + bodyHtml + "<div class=\"mt-3\"><span class=\"inline-block text-xs font-medium px-2 py-1 rounded-full border " + b.c + "\">" + b.l + "</span></div></div>";
    }

    function showTypingIndicator() {
        if (document.getElementById("typing-indicator")) return;
        if (typingTpl) { var clone = typingTpl.content.cloneNode(true); chatMessages.appendChild(clone); }
    }
    function hideTypingIndicator() { var el = document.getElementById("typing-indicator"); if (el) el.remove(); }

    function showToast(message, type) {
        var toast = document.getElementById("toast");
        if (!toast) return;
        toast.textContent = message;
        toast.className = "fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none "
            + (type === "error" ? "bg-red-900 border border-red-500 text-red-200" : "bg-zinc-800 border border-yellow-400 text-yellow-300");
        toast.style.opacity = "1";
        setTimeout(function () { toast.style.transition = "opacity 0.3s"; toast.style.opacity = "0"; setTimeout(function () { toast.style.transition = ""; }, 300); }, 3000);
    }

    function scrollToBottom() { chatMessages.scrollTop = chatMessages.scrollHeight; }
    function clearMessages()  { chatMessages.innerHTML = ""; }

    function showEmptyState() {
        if (document.getElementById("empty-state")) return;
        var el = document.createElement("div");
        el.id = "empty-state";
        el.className = "flex flex-col items-center justify-center h-full text-center gap-5 px-2 py-8";
        el.innerHTML = '<div class="w-16 h-16 rounded-full bg-yellow-400 flex items-center justify-center shadow-lg shadow-yellow-400/30"><svg class="w-9 h-9 text-black" fill="currentColor" viewBox="0 0 24 24"><path d="M20 9V7c0-1.1-.9-2-2-2h-3c0-1.66-1.34-3-3-3S9 3.34 9 5H6c-1.1 0-2 .9-2 2v2c-1.66 0-3 1.34-3 3s1.34 3 3 3v4c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-4c1.66 0 3-1.34 3-3s-1.34-3-3-3zm-2 10H6V7h12v12zm-9-6c-.83 0-1.5-.67-1.5-1.5S8.17 10 9 10s1.5.67 1.5 1.5S9.83 13 9 13zm6 0c-.83 0-1.5-.67-1.5-1.5S14.17 10 15 10s1.5.67 1.5 1.5S15.83 13 15 13z"/></svg></div>'
            + '<div><h2 class="text-2xl font-extrabold text-yellow-400 mb-2">Welcome to FitSense!</h2><p class="text-zinc-400 text-sm max-w-sm leading-relaxed">I\'m your personal AI fitness coach. What would you like to work on today?</p></div>'
            + '<div class="w-full max-w-sm space-y-2">'
            + '<button class="welcome-action-btn w-full flex items-center gap-4 px-4 py-3.5 rounded-2xl bg-zinc-900 border border-zinc-700 hover:border-yellow-400/60 hover:bg-zinc-800 transition-all text-left" data-prompt="Create a personalized workout plan for me based on my fitness level and goals"><span class="w-10 h-10 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-black" viewBox="0 0 640 512" fill="currentColor"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg></span><div><p class="text-sm font-bold text-white">Workout Plan</p><p class="text-xs text-zinc-400 mt-0.5">Get a personalized exercise routine</p></div></button>'
            + '<button class="welcome-action-btn w-full flex items-center gap-4 px-4 py-3.5 rounded-2xl bg-zinc-900 border border-zinc-700 hover:border-yellow-400/60 hover:bg-zinc-800 transition-all text-left" data-prompt="Give me a personalized meal plan and nutrition guide based on my fitness goals"><span class="w-10 h-10 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg></span><div><p class="text-sm font-bold text-white">Nutrition Guide</p><p class="text-xs text-zinc-400 mt-0.5">Healthy meal plans and tips</p></div></button>'
            + '<button class="welcome-action-btn w-full flex items-center gap-4 px-4 py-3.5 rounded-2xl bg-zinc-900 border border-zinc-700 hover:border-yellow-400/60 hover:bg-zinc-800 transition-all text-left" data-section="progress"><span class="w-10 h-10 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span><div><p class="text-sm font-bold text-white">Track Progress</p><p class="text-xs text-zinc-400 mt-0.5">Monitor your fitness journey</p></div></button>'
            + '<button class="welcome-action-btn w-full flex items-center gap-4 px-4 py-3.5 rounded-2xl bg-zinc-900 border border-zinc-700 hover:border-yellow-400/60 hover:bg-zinc-800 transition-all text-left" data-prompt="Give me motivation tips and strategies to stay consistent with my fitness goals"><span class="w-10 h-10 rounded-xl bg-yellow-400 flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></span><div><p class="text-sm font-bold text-white">Stay Motivated</p><p class="text-xs text-zinc-400 mt-0.5">Tips for consistent progress</p></div></button>'
            + '</div>';
        // Attach welcome action handlers
        el.querySelectorAll(".welcome-action-btn").forEach(function(btn){
            btn.addEventListener("click", function(){
                var section = this.dataset.section;
                var prompt  = this.dataset.prompt;
                if(section){ location.hash = section; }
                else if(prompt){
                    messageInput.value = prompt;
                    messageInput.dispatchEvent(new Event("input"));
                    messageInput.focus();
                }
            });
        });
        chatMessages.appendChild(el);
    }

    function hideEmptyState() { var el = document.getElementById("empty-state"); if (el) el.remove(); }

    function esc(str) { return String(str || "").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;"); }
    function truncate(str, len) { return str && str.length > len ? str.slice(0, len) + "..." : (str || ""); }
    function relativeDate(ts) {
        if (!ts) return "";
        var diff = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
        if (diff < 60) return "Just now";
        if (diff < 3600) return Math.floor(diff / 60) + "m ago";
        if (diff < 86400) return Math.floor(diff / 3600) + "h ago";
        if (diff < 604800) return Math.floor(diff / 86400) + "d ago";
        return new Date(ts).toLocaleDateString();
    }

    if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", init); } else { init(); }

})();

// Markdown renderer with table + typewriter support
function formatMarkdown(text) {
    if (!text) return "";
    var t = text.replace(/```json[\s\S]*?```/gi, "").trim();
    var lines = t.split("\n");
    var out = "";
    var inUl = false, inOl = false, inTable = false, tableRows = [];

    function flushList() {
        if (inUl) { out += "</ul>"; inUl = false; }
        if (inOl) { out += "</ol>"; inOl = false; }
    }
    function flushTable() {
        if (!inTable || tableRows.length === 0) return;
        var html = '<div style="overflow-x:auto;margin:8px 0"><table style="width:100%;border-collapse:collapse;font-size:.8rem">';
        tableRows.forEach(function(row, ri) {
            var cells = row.split("|").map(function(c){ return c.trim(); }).filter(function(c){ return c !== ""; });
            if (ri === 0) {
                html += "<thead><tr>" + cells.map(function(c){ return '<th style="background:#27272a;color:#fde047;padding:6px 10px;border:1px solid #3f3f46;text-align:left;font-weight:600">' + applyInline(c) + "</th>"; }).join("") + "</tr></thead><tbody>";
            } else if (/^[\s\-|:]+$/.test(row)) {
                // separator row - skip
            } else {
                html += "<tr>" + cells.map(function(c){ return '<td style="padding:5px 10px;border:1px solid #3f3f46;color:#e4e4e7">' + applyInline(c) + "</td>"; }).join("") + "</tr>";
            }
        });
        html += "</tbody></table></div>";
        out += html;
        inTable = false; tableRows = [];
    }
    function applyInline(s) {
        return s.replace(/\*\*(.+?)\*\*/g, '<strong style="color:#fff;font-weight:600">$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>');
    }

    for (var i = 0; i < lines.length; i++) {
        var l = lines[i];
        // Table row
        if (/^\|/.test(l)) {
            flushList();
            if (!inTable) inTable = true;
            tableRows.push(l);
            continue;
        }
        if (inTable) flushTable();
        // HR
        if (/^[-*]{3,}\s*$/.test(l)) { flushList(); out += '<hr style="border-color:#3f3f46;margin:8px 0">'; continue; }
        // H3
        if (/^### /.test(l)) { flushList(); out += '<p style="color:#fde047;font-weight:700;font-size:.85rem;margin:8px 0 2px">' + applyInline(l.slice(4)) + '</p>'; continue; }
        // H2
        if (/^## /.test(l)) { flushList(); out += '<p style="color:#fde047;font-weight:700;font-size:.9rem;margin:8px 0 2px">' + applyInline(l.slice(3)) + '</p>'; continue; }
        // H1
        if (/^# /.test(l)) { flushList(); out += '<p style="color:#fde047;font-weight:700;font-size:1rem;margin:8px 0 2px">' + applyInline(l.slice(2)) + '</p>'; continue; }
        // Bullet
        if (/^[*\-] /.test(l)) {
            if (inOl) { out += "</ol>"; inOl = false; }
            if (!inUl) { out += '<ul style="list-style:disc;margin:4px 0 4px 18px">'; inUl = true; }
            out += '<li style="color:#e4e4e7;margin:2px 0">' + applyInline(l.slice(2)) + '</li>';
            continue;
        }
        // Numbered
        if (/^\d+\. /.test(l)) {
            if (inUl) { out += "</ul>"; inUl = false; }
            if (!inOl) { out += '<ol style="list-style:decimal;margin:4px 0 4px 18px">'; inOl = true; }
            out += '<li style="color:#e4e4e7;margin:2px 0">' + applyInline(l.replace(/^\d+\. /,"")) + '</li>';
            continue;
        }
        flushList();
        // Blockquote
        if (/^> /.test(l)) { out += '<p style="color:#a1a1aa;border-left:3px solid #3f3f46;padding-left:8px;margin:4px 0">' + applyInline(l.slice(2)) + '</p>'; continue; }
        // Empty
        if (l.trim() === "") { out += '<br>'; continue; }
        // Normal
        out += '<p style="margin:2px 0">' + applyInline(l) + '</p>';
    }
    flushList(); flushTable();
    return '<div style="line-height:1.6">' + out + '</div>';
}

// Typewriter effect for AI messages
function typewriterEffect(el, html, speed) {
    var tmp = document.createElement("div");
    tmp.innerHTML = html;
    var plain = (tmp.textContent || tmp.innerText || "").replace(/\s+/g, " ").trim();
    var total = plain.length;
    if (total === 0) { el.innerHTML = html; return; }
    var i = 0;
    var ms = speed || 12;
    el.textContent = "";
    function tick() {
        var chunk = Math.ceil(total / 120); // scale speed to response length
        i = Math.min(i + chunk, total);
        el.textContent = plain.slice(0, i);
        if (i < total) {
            setTimeout(tick, ms);
        } else {
            el.innerHTML = html; // swap to full formatted HTML at end
        }
    }
    setTimeout(tick, ms);
}