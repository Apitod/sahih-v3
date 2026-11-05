# Phase 3.7b — Hasil Uji Semua Model di Combo `code` (gateway 9-router)

Diuji: 75 model · jalan: 27 · gagal: 48
Metode: `POST http://127.0.0.1:20128/v1/chat/completions`, prompt 'hi', max_tokens 8, paralel 8.

## Yang JALAN (27)

| Model | Waktu |
|---|---|
| `ag/gpt-oss-120b-medium` | 1.0s |
| `free` | 1.4s |
| `cbai/glm-5.2` | 1.4s |
| `cbai/glm-5.1` | 1.4s |
| `cbai/minimax-m3` | 1.4s |
| `cbai/kimi-k2.5` | 1.5s |
| `cbai/deepseek-v4.1-flash` | 1.5s |
| `ag/claude-sonnet-4-6` | 1.6s |
| `code` | 1.7s |
| `cx/gpt-5.6-luna` | 2.0s |
| `cx/gpt-5.6-luna[1m]` | 2.0s |
| `cx/gpt-5.6-luna-review` | 2.0s |
| `cx/gpt-5.6-terra-review` | 2.1s |
| `cx/codex-auto-review` | 2.1s |
| `cx/gpt-reserve` | 2.2s |
| `ag/claude-opus-4-6-thinking` | 2.3s |
| `cx/gpt-5.6-terra` | 2.3s |
| `cx/gpt-5.6-terra[1m]` | 2.5s |
| `ag/gemini-3.8-flash-low` | 3.0s |
| `cbai/kimi-k2.6` | 3.5s |
| `ag/gemini-3.8-flash-medium` | 3.6s |
| `ag/gemini-3.8-flash` | 3.8s |
| `cx/gpt-6-luna[1m]` | 4.5s |
| `cx/gpt-6-luna` | 4.6s |
| `cbai/kimi-k2.7` | 4.8s |
| `ag/gemini-3.8-flash-high` | 5.8s |
| `cbai/glm-5v-turbo` | 64.0s |

## Yang GAGAL (48), dikelompokkan

### A — model tidak terdaftar di upstream — 6 model

- `cbai/glm-5.0-turbo` → HTTP 400 — [400]: {"code":11102,"msg":"model [glm-5.0-turbo] service info not found","requestId":"766aeddc-837e-4e13-ba69-2d4fd35e0
- `cbai/glm-4.7` → HTTP 400 — [400]: {"code":11102,"msg":"model [glm-4.7] service info not found","requestId":"5e5a872a-946e-430c-8dd9-e91b31ba3f66","
- `cbai/minimax-m2.7` → HTTP 400 — [400]: {"code":11102,"msg":"model [minimax-m2.7] service info not found","requestId":"fdfd0c63-165c-4c86-9027-5e310583c6
- `cbai/hy3-preview` → HTTP 400 — [400]: {"code":11102,"msg":"model [hy3-preview] service info not found","requestId":"a8b5748f-0670-40a5-9368-f903663d60f
- `cbai/deepseek-v4-pro` → HTTP 400 — [400]: {"code":11102,"msg":"model [deepseek-v4-pro] service info not found","requestId":"4cc7e412-531b-4d3c-8a56-8e3310a
- `cbai/deepseek-v3-2-volc` → HTTP 400 — [400]: {"code":11102,"msg":"model [deepseek-v3-2-volc] service info not found","requestId":"43f0180b-a66e-4e1b-8cb3-7cd9

### B — kena limit (sementara) — 9 model

- `cbai/glm-5.0` → HTTP 503 — [codebuddy-intl/glm-5.0] [429]: {"code":14003,"msg":"too many requests","requestId":"e9f47f36-a391-428f-a67a-8872b4e36b6
- `oc/jev-1.13-free` → HTTP 429 — [429]: {"type":"error","error":{"type":"FreeUsageLimitError","message":"Free usage limit reached. Free models work in Op
- `oc/mimo-v2.6-flash-free` → HTTP 429 — [429]: {"type":"error","error":{"type":"FreeUsageLimitError","message":"Free usage limit reached. Free models work in Op
- `oc/big-pickle` → HTTP 429 — [429]: {"type":"error","error":{"type":"FreeUsageLimitError","message":"Free usage limit reached. Free models work in Op
- `oc/beam-free` → HTTP 429 — [429]: {"type":"error","error":{"type":"FreeUsageLimitError","message":"Free usage limit reached. Free models work in Op
- `oc/nemotron-3.5-super-vl-free` → HTTP 429 — [429]: {"type":"error","error":{"type":"FreeUsageLimitError","message":"Free usage limit reached. Free models work in Op
- `oc/nemotron-3.5-lightning-free` → HTTP 429 — [429]: {"type":"error","error":{"type":"FreeUsageLimitError","message":"Free usage limit reached. Free models work in Op
- `oc/deepseek-v4.1-flash-free` → HTTP 429 — [429]: {"type":"error","error":{"type":"FreeUsageLimitError","message":"Free usage limit reached. Free models work in Op
- `oc/glm-5.3-flash-free` → HTTP 429 — [429]: {"type":"error","error":{"type":"FreeUsageLimitError","message":"Free usage limit reached. Free models work in Op

### C — paket akun tidak mendukung — 9 model

- `cx/gpt-6.1-sol` → HTTP 503 — [codex/gpt-6.1-sol] [400]: {"detail":"The 'gpt-6.1-sol' model is not supported when using Codex with a ChatGPT account."
- `cx/gpt-6-astra` → HTTP 503 — [codex/gpt-6-astra] [400]: {"detail":"The 'gpt-6-astra' model is not supported when using Codex with a ChatGPT account."
- `cx/gpt-6-astra[1m]` → HTTP 503 — [codex/gpt-6-astra] [400]: {"detail":"The 'gpt-6-astra' model is not supported when using Codex with a ChatGPT account."
- `cx/gpt-6-sol` → HTTP 503 — [codex/gpt-6-sol] [400]: {"detail":"The 'gpt-6-sol' model is not supported when using Codex with a ChatGPT account.","er
- `cx/gpt-6-sol[1m]` → HTTP 503 — [codex/gpt-6-sol] [400]: {"detail":"The 'gpt-6-sol' model is not supported when using Codex with a ChatGPT account.","er
- `cx/gpt-5.6-sol` → HTTP 503 — [codex/gpt-5.6-sol] [400]: {"detail":"The 'gpt-5.6-sol' model is not supported when using Codex with a ChatGPT account."
- `cx/gpt-5.6-sol[1m]` → HTTP 503 — [codex/gpt-5.6-sol] [400]: {"detail":"The 'gpt-5.6-sol' model is not supported when using Codex with a ChatGPT account."
- `cx/gpt-5.6-sol-review` → HTTP 503 — [codex/gpt-5.6-sol-review] [400]: {"detail":"The 'gpt-5.6-sol' model is not supported when using Codex with a ChatGPT ac
- `cx/gpt-daybreak-blue-latest` → HTTP 503 — [codex/gpt-daybreak-blue-latest] [400]: {"detail":"The 'gpt-daybreak-blue-latest' model is not supported when using Code

### D — tidak ada di akun/region — 18 model

- `ag/gemini-4-argon-high` → HTTP 503 — [antigravity/gemini-4-argon-high] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not found.
- `ag/gemini-3.7-flash-high` → HTTP 503 — [antigravity/gemini-3.7-flash-high] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not foun
- `ag/gemini-3.7-flash-medium` → HTTP 503 — [antigravity/gemini-3.7-flash-medium] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not fo
- `ag/gemini-3.7-flash-low` → HTTP 503 — [antigravity/gemini-3.7-flash-low] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not found
- `ag/gemini-3.6-flash-high` → HTTP 503 — [antigravity/gemini-3.6-flash-high] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not foun
- `ag/gemini-3.6-flash-medium` → HTTP 503 — [antigravity/gemini-3.6-flash-medium] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not fo
- `ag/gemini-3.6-flash-low` → HTTP 503 — [antigravity/gemini-3.6-flash-low] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not found
- `ag/gemini-3.1-pro-high` → HTTP 503 — [antigravity/gemini-3.1-pro-high] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not found.
- `ag/gemini-pro-agent` → HTTP 503 — [antigravity/gemini-pro-agent] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not found.", 
- `ag/gemini-3.1-pro-low` → HTTP 503 — [antigravity/gemini-3.1-pro-low] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not found."
- `ag/claude-sonnet-5-5-high` → HTTP 503 — [antigravity/claude-sonnet-5-5-high] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not fou
- `ag/claude-sonnet-5-5-medium` → HTTP 503 — [antigravity/claude-sonnet-5-5-medium] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not f
- `ag/claude-sonnet-5-5-low` → HTTP 503 — [antigravity/claude-sonnet-5-5-low] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not foun
- `ag/claude-sonnet-5-5` → HTTP 503 — [antigravity/claude-sonnet-5-5] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not found.",
- `ag/claude-opus-5-5-high` → HTTP 503 — [antigravity/claude-opus-5-5-high] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not found
- `ag/claude-opus-5-5-medium` → HTTP 503 — [antigravity/claude-opus-5-5-medium] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not fou
- `ag/claude-opus-5-5-low` → HTTP 503 — [antigravity/claude-opus-5-5-low] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not found.
- `ag/claude-opus-5-5` → HTTP 503 — [antigravity/claude-opus-5-5] [404]: {   "error": {     "code": 404,     "message": "Requested entity was not found.",  

### E — upstream menolak — 6 model

- `mmf/mimo-auto` → HTTP 400 — [400]: {     "error": {         "code": "400",         "message": "Unsupported model mimo-auto"     } } 
- `oc/muse-spark-1.2-contributor-free` → HTTP 400 — [400]: {"error":{"type":"server_error","message":"Upstream request failed: Model is unavailable."}}
- `oc/muse-spark-1.3-contributor-free` → HTTP 400 — [400]: {"error":{"type":"server_error","message":"Upstream request failed: Model is unavailable."}}
- `oc/union-alpha` → HTTP 400 — [400]: {"type":"error","error":{"type":"api_error","message":"Upstream request failed: Model is unavailable."}}
- `oc/longcat-2.5-preview-free` → HTTP 400 — [400]: {"error":{"type":"server_error","message":"Upstream request failed: Model is unavailable."}}
- `oc/minimax-m3.1-flash-free` → HTTP 400 — [400]: {"error":{"type":"server_error","message":"Upstream request failed: Model is unavailable."}}

## Pembersihan (dilakukan atas persetujuan Razan)

- Dihapus: **39 model permanen mati** (grup A, C, D, E) dari **dua** provider (`Code` dan `free`) = 78 baris.
- Disimpan: 27 yang jalan + 9 yang hanya kena limit (grup B).
- Hasil akhir: **36 model per provider**, diff bersih (78 dihapus, 0 ditambah).
- Verifikasi ulang lewat Hermes: 36 model → **27 OK, 9 gagal (semua grup B / limit)**.
- Backup sebelum edit: `~/.hermes/config.yaml.bak-20261030-195600` (restore: `cp` balik file itu).
