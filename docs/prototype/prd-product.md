# **PRD — IT Monitoring Portal (RS Awal Bros Botania)**

**Oct 25, 2026 · @sasha · Status: Draft v1.0**

## **1. Overview and problem statement**

**The IT Monitoring Portal lets one admin in the IT division of RS Awal Bros Botania record, review and export every recurring IT monitoring check in one place. The MVP covers three modules: Server Room Temperature (monthly), CCTV Monitoring (daily) and Clear Desk & Clear Screen (daily, ISO 27001).**

**Problem today.**** IT staff fill in several separate forms in separate tools, one by one. The process is slow, data ends up in different places, and errors go unnoticed. There is no single history to review and no consistent report format for audits.**

**Solution.**** A web portal reachable only inside the hospital network where:**

- **The admin fills in each check through one form per module and submits it to a central database.**
- **A dashboard shows what has been done today and this month.**
- **Each module has a recap page with summary statistics, filters, search, and export to Excel, PDF and print.**
- **Every change is recorded in a change log.**

**Scope source.**** This PRD is based on the clickable HTML prototype (`rs-awal-bros-portal`) and two rounds of interview answers. Where the prototype and the answers differ, the answers win.**

**Glossary**

| **Term** | **Meaning** |
| --- | --- |
| **Admin** | **The single person who logs in and enters all data** |
| **Entry** | **One submitted record in a module (one temperature reading, one CCTV check, one Clear Desk session)** |
| **Draft** | **An entry saved but not submitted. Drafts never count toward progress or recaps** |
| **Rekap** | **The recap/history page of a module** |
| **Shift** | **Pagi 07:00–13:59, Siang 14:00–20:59, Malam 21:00–06:59 (WIB)** |
| **WIB** | **Western Indonesia Time, UTC+7. All times in the system use WIB** |

## **2. Goals and non-goals**

**Goals (MVP)**

1. **One admin can enter all three monitoring checks from one portal, in one login session.**
2. **Every submitted entry is stored permanently and can be found again by period, status, location and free-text search.**
3. **The dashboard shows, from real saved data, whether today's daily checks and this month's temperature readings are done.**
4. **Every recap page exports the rows on screen to Excel and PDF, and prints, in one consistent hospital format.**
5. **Status rules (temperature bands, compliance) are calculated by the system, not typed by the admin.**
6. **Every create, edit, delete and export is traceable in a change log.**

**Non-goals (MVP)**

- **Multiple users, roles, permissions or a user-management page.**
- **Approval or verification workflow inside the app (the Head of IT signs the printed report).**
- **Closing or locking a period (Phase 2).**
- **Master lists of cameras, workstations or units (Phase 2). MVP uses a configured total-camera number and a fixed list of sensor locations.**
- **A Settings page for thresholds (thresholds live in configuration).**
- **Automatic data capture from sensors, NVRs or network tools. All data is entered by hand.**
- **Notifications by email, WhatsApp or push. The 25th-of-month reminder is shown on the dashboard only.**
- **Self-service password reset.**
- **Hardware Server IT, User Access Conformity (HIS) and BPJS Online Queue modules (Phase 2).**
- **Access from outside the hospital network other than through the existing VPN.**

## **3. Success metrics**

**Success means the admin stops using the old separate forms within the first month and every daily check is recorded on time. Baselines for the current process are not measured yet (see Open questions), so targets below are absolute.**

| **Metric** | **How it is measured** | **Target** | **Checked** |
| --- | --- | --- | --- |
| **Adoption** | **Share of monitoring checks entered in the portal vs. any other tool** | **100% from week 5 after go-live** | **Monthly, by the Head of IT** |
| **Daily completeness** | **Days where CCTV and Clear Desk both have ≥ 1 submitted entry ÷ working days in the month** | **≥ 95%** | **Monthly, from the database** |
| **Monthly completeness** | **Months where all 4 temperature locations are submitted by the last day of the month** | **100%** | **Monthly** |
| **Time to record a CCTV check** | **Median time from opening the form to a successful submit (server log)** | **≤ 3 minutes** | **After 4 weeks** |
| **Time per Clear Desk workstation row** | **Median time between consecutive "Save and add next" actions** | **≤ 60 seconds** | **After 4 weeks** |
| **Report production** | **Time from opening a recap to having the monthly PDF** | **≤ 2 minutes, no manual editing** | **At first audit** |
| **Data errors** | **Entries edited within 24 hours of submit for a correction ÷ all entries** | **≤ 5%** | **Monthly, from the change log** |
| **Availability** | **Uptime during 06:00–23:00 WIB** | **≥ 99% per month** | **Monthly** |

## **4. User and persona**

**There is exactly one user: the IT admin. Every entry is attributed to this account automatically; there is no inspector field.**

**Persona — IT admin, RS Awal Bros Botania**

- **Role.**** IT staff member responsible for daily and monthly IT monitoring and for ISO 27001 / KARS audit evidence.**
- **Where they work.**** At a desk in the IT room for monthly entry and reports; walking around wards, server rooms and offices with a phone or tablet for CCTV and Clear Desk checks.**
- **Frequency.**** Several times a day (Clear Desk rows, CCTV), monthly (temperature, reports).**
- **Needs.**** Fast entry on a small screen, no retyping of known workstation details, clear "what is still missing today" signal, audit-ready reports without manual formatting.**
- **Frustrations today.**** Separate forms and tools, no history, inconsistent numbers between reports.**
- **Technical comfort.**** High (IT professional). Can reset a password on the server when needed.**

**Secondary stakeholders (not users)**

| **Stakeholder** | **Interest** | **Interaction with the system** |
| --- | --- | --- |
| **Kepala Instalasi IT (Head of IT)** | **Reviews and signs monthly reports** | **Receives exported PDF / printout; signs the "Diketahui oleh" block on paper** |
| **Internal / external auditors (ISO 27001, KARS)** | **Evidence that checks happen and findings are followed up** | **Receives PDF / Excel exports** |
| **Hospital management** | **Compliance and risk overview** | **Receives reports from the Head of IT** |

## **5. User stories**

**All stories are written for the admin. IDs are referenced by the functional requirements and acceptance criteria.**

| **ID** | **As the admin, I want to…** | **So that…** | **Priority** |
| --- | --- | --- | --- |
| **US-01** | **log in with my username and password** | **only I can see and change hospital IT data** | **Must** |
| **US-02** | **change my password from the Account menu** | **I can rotate it without server access** | **Must** |
| **US-03** | **see on the dashboard which daily checks are done today and how many temperature locations are done this month** | **I know what is still missing** | **Must** |
| **US-04** | **see a reminder from the 25th when temperature entries are incomplete** | **the month is not closed with gaps** | **Must** |
| **US-05** | **record the monthly average temperature for one sensor location with an evidence file** | **the server room condition is documented for audit** | **Must** |
| **US-06** | **have the temperature status calculated for me** | **status is consistent and cannot be mistyped** | **Must** |
| **US-07** | **record a CCTV check with broken cameras, NTP clock sync and NVR health** | **camera problems are tracked per day and shift** | **Must** |
| **US-08** | **record a Clear Desk session with as many workstation rows as I inspect** | **I can inspect any number of computers in one round** | **Must** |
| **US-09** | **have location, IP and OS filled in when I type a known MAC address** | **I don't retype known workstation details** | **Must** |
| **US-10** | **save a form as a draft and finish it later** | **I don't lose work when interrupted** | **Must** |
| **US-11** | **edit any submitted entry** | **I can correct mistakes** | **Must** |
| **US-12** | **delete an entry with a reason** | **wrong entries disappear from recaps but remain traceable** | **Must** |
| **US-13** | **filter and search each recap** | **I find specific entries quickly** | **Must** |
| **US-14** | **export the filtered recap to Excel and to a monthly PDF, and print it** | **I can hand audit-ready reports to the Head of IT and auditors** | **Must** |
| **US-15** | **see who changed what and when** | **I can prove data integrity in an audit** | **Must** |
| **US-16** | **use every form comfortably on a phone** | **I can enter data while walking around** | **Must** |
| **US-17** | **have the shift picked automatically from the current time** | **I save a step on every entry** | **Should** |

## **6. Functional requirements per module**

**Each module has one Form page and one Rekap page. Behaviour shared by all forms is defined once in 6.1; module-specific fields, validation, status logic and formulas follow in 6.2–6.4. Each requirement carries its acceptance criteria (AC).**

### **6.1 Shared form behaviour**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **FR-F1** | **Draft.**** "Simpan Draft" saves the form server-side with status `draft`. Only type checks apply (e.g. a number field must hold a number); required-field checks do not.** | **Given a half-filled form, when the admin taps Simpan Draft, then a draft is stored, a toast "Draft tersimpan" shows, and the draft reappears under "Lanjutkan draft" at the top of that module's form. Drafts never appear in recaps, progress or exports.** |
| **FR-F2** | **Submit.**** "Kirim" validates all rules, stores the entry with status `submitted`, and shows a success dialog with the entry number and two actions: "Input baru" and "Lihat rekap".** | **Valid form → one record saved and dialog shown within 2 s. Invalid form → nothing saved, page scrolls to the first error, each invalid field shows its message under the field.** |
| **FR-F3** | **No double submit.**** Each form load carries a one-time token; the submit button is disabled while the request runs.** | **Tapping Kirim twice quickly, or resubmitting after a browser back, creates exactly one entry.** |
| **FR-F4** | **Entry number.**** Format `<MOD>-<YYYYMM>-<NNNN>`, MOD = `TMP`, `CCTV`, `CDS`; NNNN restarts at 0001 each month.** | **First CCTV entry in October 2026 = `CCTV-202610-0001`. Numbers are never reused, including after delete.** |
| **FR-F5** | **Inspection date-time.**** Defaults to the current server time (WIB); editable; may not be more than 5 minutes in the future.** | **Entering tomorrow's date shows "Waktu tidak boleh di masa depan" and blocks submit.** |
| **FR-F6** | **Shift auto-detect**** (CCTV, Clear Desk). Shift is derived from the inspection time: Pagi 07:00–13:59, Siang 14:00–20:59, Malam 21:00–06:59. The admin can override it. *Assumption: shift hours taken from the prototype's CCTV schedule.*** | **Time 15:30 → Siang preselected. Changing the time to 22:10 before the admin touched the shift field updates it to Malam; after a manual override it no longer changes.** |
| **FR-F7** | **Confirmation checkbox.**** Each form has a required declaration checkbox before submit.** | **Submit with the box unticked shows "Centang pernyataan untuk mengirim" and saves nothing.** |
| **FR-F8** | **Edit.**** Any submitted entry can be edited at any time from its Rekap detail. Saving re-runs validation and status calculation using the thresholds stored on that entry (see FR-T5).** | **After an edit, the recap shows the new values; the change log has one record with old and new values of every changed field.** |
| **FR-F9** | **Soft delete.**** Delete requires a reason of 10–500 characters. The entry is hidden from recaps, progress and exports but kept in the database.** | **After deleting, the entry is gone from the recap and totals; the change log shows the delete with reason and timestamp. The entry number is not reused.** |
| **FR-F10** | **Unsaved changes.**** Leaving a form with unsaved input asks for confirmation.** | **Typing in any field, then tapping a nav link, shows "Perubahan belum disimpan. Tinggalkan halaman?"** |
| **FR-F11** | **File upload.**** Allowed types and limits are set per field (below). Files are checked by extension AND content type, renamed to a random ID, stored outside the web root and served only to a logged-in session.** | **A 12 MB file, or a `.exe` renamed to `.pdf`, is rejected with a message naming the limit. An uploaded file opens from the recap only when logged in.** |
| **FR-F12** | **Language and format.**** All UI text is Bahasa Indonesia. Dates display as `15 Okt 2026, 10:18 WIB`; decimals use a comma in display (`21,5 °C`) and accept both `21.5` and `21,5` on input.** | **No English UI strings remain (e.g. "Add file" → "Tambah berkas"). Entering `21,5` stores 21.5.** |

### **6.2 Module: Server Room Temperature (monthly)**

**One entry = one sensor location for one month. Four locations × one entry each = a complete month.**

**Fields**

| **Field** | **Type** | **Required** | **Validation and rules** |
| --- | --- | --- | --- |
| **Periode (month)** | **Month picker** | **Yes** | **Default = current month. Not in the future.** |
| **Waktu pengambilan data** | **Date-time** | **Yes** | **FR-F5. Must be on or after the first day of the selected month.** |
| **Lokasi sensor** | **Radio, 4 fixed options** | **Yes** | **Ruang Server Utama (Data Center A) · SNS-DCA-01; Rak Server 1 (Core Switch & Storage) · SNS-RK1-04; Rak Server 2 (Virtualization & DB) · SNS-RK2-09; Ruang UPS & Backup Power · SNS-UPS-02. Locations come from configuration.** |
| **Rata-rata suhu (°C)** | **Decimal, 1 place** | **Yes** | **0.0–60.0. Rounded to 1 decimal on save.** |
| **Kelembapan (% RH)** | **Decimal, 1 place** | **No** | **0.0–100.0.** |
| **Bukti / log sensor** | **File, 1** | **Yes** | **PDF, CSV, XLSX, JPG, PNG; max 10 MB.** |
| **Catatan AC / PAC** | **Text area** | **No** | **Max 2,000 characters.** |
| **Pernyataan** | **Checkbox** | **Yes** | **FR-F7.** |

**Rules and status logic**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **FR-T1** | **One entry per location per month.**** A second submitted entry for the same location and month is blocked.** | **Submitting Rak Server 1 for Oct 2026 when one exists shows "Lokasi ini sudah diisi untuk Oktober 2026" with a link to edit the existing entry; nothing is saved.** |
| **FR-T2** | **Temperature status**** is computed from the rounded value. *Assumption pending SOP confirmation.*** | **18.0–24.0 → Normal; 24.1–26.9 → Peringatan; < 18.0 → Peringatan; ≥ 27.0 → Kritis. Test values: 17.9 Peringatan, 18.0 Normal, 24.0 Normal, 24.1 Peringatan, 26.9 Peringatan, 27.0 Kritis.** |
| **FR-T3** | **Humidity status**** (only when humidity is entered). *Assumption: Normal 45.0–60.0 % RH, otherwise Peringatan.*** | **44.9 → Peringatan, 45.0 → Normal, 60.0 → Normal, 60.1 → Peringatan, empty → no humidity status.** |
| **FR-T4** | **Live status preview.**** The status chip updates as the admin types, using the same bands, before submit.** | **Typing 25 shows the Peringatan chip immediately; the saved status equals the preview.** |
| **FR-T5** | **Threshold snapshot.**** Each entry stores the bands in force when it was first submitted. Status on edit uses the stored bands; changing configuration never changes old entries.** | **Change the Normal upper bound to 25.0 in config; an existing 24.5 entry stays Peringatan; a new 24.5 entry is Normal.** |
| **FR-T6** | **Side panel = this month's coverage.**** Replaces the prototype's "LIVE IoT" and "Tren 24 jam" panels, which have no data source. Shows the 4 locations for the selected month, each with its value and status or "Belum diisi".** | **With 3 of 4 entries saved, the panel lists 3 values with status chips and 1 "Belum diisi" row linking to a pre-filled form.** |

**Rekap statistics (computed on the filtered set)**

- **Rata-rata suhu = mean of `avg_temp` over filtered entries, 1 decimal.**
- **Suhu minimum / maksimum = lowest / highest `avg_temp`, with its location and month.**
- **Pembacaan di luar batas = count of entries with status Peringatan or Kritis (replaces the prototype's "Insiden spike", which cannot be derived from monthly averages).**
- **Kelengkapan = submitted locations ÷ 4 for the selected month, shown as "3 dari 4 lokasi". Hidden when the filter spans more than one month.**

### **6.3 Module: CCTV Monitoring (daily)**

**One entry = one CCTV check, normally one per shift. Several entries per day are allowed.**

**Fields**

| **Field** | **Type** | **Required** | **Validation and rules** |
| --- | --- | --- | --- |
| **Waktu pemeriksaan** | **Date-time** | **Yes** | **FR-F5.** |
| **Shift** | **Select (Pagi, Siang, Malam)** | **Yes** | **FR-F6.** |
| **Total kamera** | **Read-only number** | **—** | **From configuration (e.g. 68). Stored on the entry as a snapshot.** |
| **Kamera bermasalah** | **Toggle "NIHIL — semua kamera normal" OR repeatable rows** | **Yes** | **Either NIHIL is on, or at least 1 row exists. Max rows = total cameras.** |
| **→ Nama / channel kamera** | **Text** | **Yes, per row** | **2–100 characters, e.g. `CCTV-UGD-02`.** |
| **→ Alamat IP** | **IPv4** | **Yes, per row** | **Valid IPv4 (e.g. 192.168.10.42).** |
| **→ Zona gedung** | **Select from config list** | **Yes, per row** | ***Assumption: the hospital supplies the zone list (e.g. UGD, Parkir Timur, Lobby Farmasi).*** |
| **→ Jenis masalah** | **Select** | **Yes, per row** | **Offline / putus jaringan; Buram / kaca berembun; Sudut bergeser; Gagal merekam; Lainnya.** |
| **→ Keterangan** | **Text** | **If "Lainnya"** | **Max 200 characters.** |
| **Sinkronisasi jam (NTP)** | **Radio** | **Yes** | **Sesuai; Tidak sesuai; Lainnya.** |
| **→ Keterangan sinkronisasi** | **Text** | **If not "Sesuai"** | **Max 300 characters (e.g. selisih waktu).** |
| **Status NVR / storage** | **Radio** | **Yes** | **Normal (retensi ≥ 30 hari); Kritis (kapasitas > 90% atau retensi < 30 hari).** |
| **→ Retensi (hari)** | **Integer** | **No** | **0–365. If entered and < 30, status is forced to Kritis.** |
| **→ Keterangan NVR** | **Text** | **If Kritis** | **Max 300 characters.** |
| **Bukti / screenshot** | **Files, up to 5** | **No** | **JPG, PNG, PDF; max 10 MB each.** |
| **Catatan serah terima** | **Text area** | **No** | **Max 2,000 characters.** |
| **Pernyataan** | **Checkbox** | **Yes** | **FR-F7.** |

***Assumption (challenges the "broken cameras as free text" default):* broken cameras are entered as structured rows with free-text names, not one text box. Rows let the system count broken cameras, compute working cameras and filter by zone without a camera master list.**

**Rules, status logic and formulas**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **FR-C1** | **Broken / working count.**** broken = number of rows (0 when NIHIL); working = total cameras snapshot − broken; availability % = working ÷ total × 100, 1 decimal.** | **Total 68, 2 rows → working 66, availability 97.1%.** |
| **FR-C2** | **Entry status.**** Normal = 0 broken AND NTP Sesuai AND NVR Normal. Kritis = NVR Kritis OR availability < 90%. Otherwise Peringatan. *Assumption: 90% threshold, in configuration.*** | **(0, Sesuai, Normal) → Normal; (2 broken of 68, Sesuai, Normal) → Peringatan; (0, Tidak sesuai, Normal) → Peringatan; (0, Sesuai, Kritis) → Kritis; (8 of 68 = 88.2%) → Kritis.** |
| **FR-C3** | **Duplicate shift warning.**** If an entry already exists for the same date and shift, submit asks for confirmation.** | **Second Pagi entry today → dialog "Shift Pagi hari ini sudah diisi. Tetap kirim?"; Ya saves, Batal returns to the form.** |
| **FR-C4** | **Copy from last check.**** A "Salin dari pemeriksaan terakhir" action pre-fills broken-camera rows from the most recent submitted entry.** | **With yesterday's entry listing 2 cameras, the action adds those 2 rows; the admin can remove rows before submit.** |
| **FR-C5** | **Side panel**** shows the latest submitted entry's status, broken camera list and today's shifts recorded (replaces the prototype's "LIVE NVR" panel).** | **After submitting Pagi, the panel shows Pagi ✓, Siang and Malam "Belum".** |

**Rekap statistics (filtered set)**

- **Total kamera = current configured total.**
- **Kamera berfungsi = working count from the most recent entry in the filter.**
- **Insiden kamera = total broken-camera rows across filtered entries.**
- **Sinkronisasi jam = entries with NTP Sesuai ÷ filtered entries × 100, 1 decimal.**

### **6.4 Module: Clear Desk & Clear Screen (daily, ISO 27001)**

**One entry = one inspection session holding a dynamic list of workstation rows, with no maximum.**

**Session fields**

| **Field** | **Type** | **Required** | **Validation and rules** |
| --- | --- | --- | --- |
| **Waktu mulai** | **Date-time** | **Yes** | **FR-F5; default = when the form opened.** |
| **Shift** | **Select** | **Yes** | **FR-F6.** |
| **Catatan sesi** | **Text area** | **No** | **Max 2,000 characters.** |
| **Pernyataan** | **Checkbox** | **Yes** | **FR-F7.** |

**Workstation row fields**

| **Field** | **Type** | **Required** | **Validation and rules** |
| --- | --- | --- | --- |
| **MAC address** | **Text** | **Yes** | **Accepts `:` `-` `.` or no separators, any case; stored as `AA:BB:CC:DD:EE:FF`. Must be unique within the session.** |
| **Lokasi / unit** | **Text with suggestions** | **Yes** | **2–150 characters; suggestions from previously used values.** |
| **Alamat IP** | **IPv4** | **Yes** | **Valid IPv4.** |
| **Sistem operasi** | **Select** | **Yes** | **Windows 11; Windows 10; Windows 7/8; Linux; macOS; Lainnya (+ text, max 50).** |
| **Status kepatuhan** | **Radio** | **Yes** | **Patuh; Temuan.** |
| **Kategori temuan** | **Multi-select** | **If Temuan** | **Password tertulis/terlihat; Layar tidak terkunci; Dokumen sensitif di meja; Media penyimpanan tertinggal; Lainnya.** |
| **Keterangan temuan** | **Text** | **If Temuan** | **5–500 characters.** |
| **Tindak lanjut** | **Select** | **No** | **Ditegur di tempat; Dilaporkan ke atasan unit; Lainnya.** |
| **Foto bukti** | **File, 1** | **No** | **JPG, PNG; max 10 MB.** |

**Rules and formulas**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **FR-D1** | **Dynamic rows.**** Rows can be added and removed freely; no maximum. Removing a row that has data asks for confirmation.** | **A session with 150 rows saves and reloads complete; the form stays responsive (typing latency < 100 ms) at 200 rows on a mid-range phone.** |
| **FR-D2** | **Save and add next.**** "Simpan & tambah berikutnya" validates the current row, saves the session as a draft, appends an empty row and puts focus in its MAC field.** | **Valid row → new empty row appears with the cursor in MAC; reloading the page restores all saved rows. Invalid row → errors shown, no new row.** |
| **FR-D3** | **MAC auto-fill.**** When a MAC loses focus, the system looks up the most recent submitted row with the same normalised MAC and fills location, IP and OS that are still empty. A note shows the source date. All filled values stay editable.** | **Enter `00-1a-2b-44-88-91` seen on 14 Okt → location, IP, OS filled; note "Diisi dari inspeksi 14 Okt 2026". Unknown MAC → nothing filled, no error. Fields already typed are not overwritten.** |
| **FR-D4** | **Submit**** needs ≥ 1 complete row.** | **Submitting with 0 rows shows "Tambahkan minimal 1 workstation".** |
| **FR-D5** | **Formulas.**** Workstations inspected = row count; Compliance rate = Patuh rows ÷ all rows × 100, 1 decimal; Violations = Temuan rows. *Assumption: hospital target 95%, in configuration.*** | **142 rows, 137 Patuh → rate 96.5%, violations 5; rate shown green when ≥ target, amber otherwise.** |
| **FR-D6** | **Recap grain.**** The Clear Desk recap lists one row per workstation inspection, with the session number as a column and a link to the whole session.** | **A session with 12 rows produces 12 recap rows sharing one session number.** |

## **7. Dashboard and progress logic**

**The dashboard shows two independent progress figures, both computed live from submitted, non-deleted entries: daily ("X dari 2 pemeriksaan harian selesai hari ini") and monthly temperature ("X dari 4 lokasi"). Drafts never count.**

**Definitions**

- **Today**** = the calendar date in WIB of the entry's inspection date-time (not the time it was typed in). *Assumption: a night-shift check at 01:30 counts for the new calendar day.***
- **CCTV done today**** = at least 1 submitted CCTV entry dated today.**
- **Clear Desk done today**** = at least 1 submitted Clear Desk session dated today.**
- **Daily progress**** = done modules ÷ 2. Temperature is not part of this ratio.**
- **Monthly temperature progress**** = distinct locations with a submitted entry for the current month ÷ 4.**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **FR-P1** | **Daily progress card shows the ratio, a bar, and each daily module as a row with state (Selesai with time of last entry, or Belum) and one action ("Lihat rekap" or "Isi sekarang").** | **No entries today → "0 dari 2", both rows Belum. After one CCTV submit → "1 dari 2", CCTV Selesai with its time.** |
| **FR-P2** | **Monthly temperature card shows "X dari 4 lokasi — \<Bulan YYYY>" and lists missing locations.** | **3 entries in Oct 2026 → "3 dari 4 lokasi — Oktober 2026", listing the 1 missing location with a link to a pre-filled form.** |
| **FR-P3** | **Reminder.**** From the 25th to the last day of the month, while temperature progress < 4, a warning banner shows on the dashboard.** | **On 24 Oct with 3/4: no banner. On 25 Oct with 3/4: banner "Data suhu Oktober belum lengkap (3/4). Batas akhir 31 Okt." On 25 Oct with 4/4: no banner.** |
| **FR-P4** | **Progress updates without manual refresh after returning to the dashboard from a submit.** | **Submit a form, tap "Kembali ke beranda": the new count shows on first render.** |
| **FR-P5** | **Status signals are truthful.**** Module cards show no "LIVE" badge (all data is manual). A "Baru" badge, if used, is shown only for 30 days after a module's release date in config. Duplicate cards (Dashboard, HIS) are removed; links to systems outside this portal are marked as external links.** | **No card shows LIVE. A module released 1 Oct shows Baru until 30 Oct, not on 31 Oct.** |
| **FR-P6** | **Latest exceptions.**** A list shows the 5 most recent entries with status Peringatan or Kritis across all modules, newest first, each linking to its detail.** | **After a 27.3 °C entry, it appears at the top with a Kritis chip.** |

***Phase 2:* per-shift completion ("CCTV 2 dari 3 shift"), a working-day calendar that excludes holidays, and month-close status.**

## **8. Recap, filter and search requirements**

**Every Rekap page has the same structure: page header with "Input baru", 4 statistic cards, a filter bar, the table, pagination, and an export group. Statistic cards and exports always reflect the active filters.**

**Common behaviour**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **FR-R1** | **Default view**** = current month, all statuses, all locations, newest inspection first.** | **Opening a recap on 15 Oct shows only October entries, sorted newest first.** |
| **FR-R2** | **Period filter**** offers a month picker plus a custom date range (from–to, max 366 days).** | **Range 1 Jan–31 Mar shows Q1 entries; a 400-day range shows "Rentang maksimal 366 hari".** |
| **FR-R3** | **Quick search**** matches, case-insensitive and partial: entry number, location/unit, notes, and module-specific fields (camera name and IP for CCTV; MAC, IP, OS for Clear Desk). Search runs 300 ms after typing stops.** | **Typing `10.42` in CCTV finds the entry with camera IP 192.168.10.42. Typing `1a:2b` in Clear Desk finds MAC 00:1A:2B:44:88:91.** |
| **FR-R4** | **Filters combine with AND. The URL holds the filter state, so a refreshed or bookmarked page shows the same result.** | **Set status = Kritis and month = Sep, reload: same rows.** |
| **FR-R5** | **A "Reset filter" link restores the default view. A count line shows "Menampilkan 1–25 dari N entri".** | **With 30 matches the line reads "Menampilkan 1–25 dari 30 entri".** |
| **FR-R6** | **Pagination**** 25 rows per page (selectable 25 / 50 / 100). Sorting by any date, value or status column.** | **Clicking the temperature header sorts ascending, again descending.** |
| **FR-R7** | **Row actions****: Lihat detail, Ubah, Hapus (FR-F8, FR-F9). Detail shows all fields, attachments and the entry's change history.** | **Detail of an edited entry lists each edit with time, field, old and new value.** |
| **FR-R8** | **Empty result shows "Tidak ada entri untuk filter ini" and the reset link; stat cards show "–" rather than 0%.** | **Filter with no matches → compliance card shows "–", not "0%".** |
| **FR-R9** | **Mobile****: below 768 px the table becomes a card list (one card per row: number, date, location, value, status); filters collapse into a "Filter" sheet. No horizontal page scroll.** | **At 390 px width, document scroll width = 390 px.** |

**Module-specific columns and filters**

| **Module** | **Table columns** | **Extra filters** |
| --- | --- | --- |
| **Temperature** | **No. entri · Periode · Waktu pengambilan · Lokasi · Suhu (°C) · Status suhu · Kelembapan (% RH) · Bukti · Catatan** | **Status suhu (Normal / Peringatan / Kritis), Lokasi sensor** |
| **CCTV** | **No. entri · Waktu · Shift · Kamera berfungsi / total · Kamera bermasalah (names) · NTP · NVR · Status · Catatan** | **Status sinkronisasi jam, Kondisi kamera (Semua normal / Ada bermasalah), Zona gedung, Shift, Status** |
| **Clear Desk** | **No. sesi · Waktu · Shift · Lokasi / unit · MAC · IP · OS · Status kepatuhan · Kategori temuan · Tindak lanjut · Foto** | **Status kepatuhan, Lokasi / unit (from used values), Sistem operasi, Shift** |

**The "Petugas IT" and "Verified" columns from the prototype are removed: there is one user and no in-app verification.**

## **9. Export and print requirements**

**Each Rekap page offers three outputs grouped under one secondary "Ekspor" button: Excel, PDF and Cetak. All three use exactly the rows and statistics of the active filters.**

***Assumption (refines the "one sheet per module" default):* an export from a recap page covers that one module, so each workbook has a data sheet and a summary sheet. A single workbook with one sheet per module is a Phase 2 "Ekspor semua modul" action on the dashboard.**

**Excel (.xlsx)**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **FR-E1** | **Sheet "Data" = all filtered rows (not only the current page), columns as in section 8, header row frozen, auto-filter on, column widths fitted. Clear Desk exports one row per workstation.** | **A filter matching 142 rows exports 142 data rows plus 1 header row, even when the screen shows page 1 of 6.** |
| **FR-E2** | **Numbers, dates and times are real Excel types (not text): temperature as number with 1 decimal, date-times as Excel dates formatted `dd/mm/yyyy hh:mm`.** | **Summing the temperature column in Excel works without conversion.** |
| **FR-E3** | **Sheet "Ringkasan" = hospital name, module, period, applied filters, generation time and the 4 statistics.** | **The summary sheet lists "Status: Kritis" when that filter was active.** |
| **FR-E4** | **File name: `<modul>_<YYYYMMDD>-<YYYYMMDD>_<generated YYYYMMDDHHmm>.xlsx`, e.g. `rekap-suhu_20261001-20261031_202611011015.xlsx`.** | **Downloaded name follows the pattern.** |

**PDF (monthly recap)**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **FR-E5** | **A4 landscape, 15 mm margins, IBM Plex Sans (embedded), body 9 pt, table header repeated on each page.** | **Opened in a PDF reader: page size 297 × 210 mm; header row visible on page 2.** |
| **FR-E6** | **Header**** on every page: hospital logo (left), "RS Awal Bros Botania — Instalasi Teknologi Informasi", report title (e.g. "Rekap Monitoring Suhu Ruang Server"), period, applied filters.** | **Logo and title appear on each page.** |
| **FR-E7** | **Body****: statistics block (4 values) then the table. Status shown as text (Normal / Peringatan / Kritis), not colour only.** | **The PDF stays readable when printed in black and white.** |
| **FR-E8** | **Signature block**** on the last page, kept together (not split across pages): left "Dibuat oleh," + admin display name + date; right "Diketahui oleh, Kepala Instalasi IT" + a blank signature line + the name from configuration.** | **The block never starts at the bottom of one page and continues on the next.** |
| **FR-E9** | **Footer**** on each page: "Dicetak \<date time> WIB · Halaman X dari Y" and the report ID (hash of filters + time) for traceability.** | **Footer of page 2 of 3 reads "Halaman 2 dari 3".** |
| **FR-E10** | **Clear Desk PDF shows workstation rows grouped by session, with session subtotals (inspected, Patuh, Temuan).** | **A 2-session month shows 2 groups, each with its subtotal line.** |

**Print (Cetak Rekap)**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **FR-E11** | **"Cetak" opens the same layout as the PDF (FR-E5–E10) in a print view and starts the browser print dialog. Navigation, filters and buttons are not printed.** | **Print preview matches the PDF page for page.** |

**Limits and logging**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **FR-E12** | **Excel max 50,000 rows; PDF/print max 2,000 rows. Above the limit, the button explains how to narrow the filter.** | **A 2,500-row PDF request shows "Maksimal 2.000 baris untuk PDF. Persempit filter."** |
| **FR-E13** | **Every export writes a change-log record: module, format, filters, row count, time.** | **Exporting a PDF adds one log record of type `export`.** |

**The official hospital report format (letterhead, document numbering, form code) is not yet known — see Open questions. The layout above is the default until a template is provided.**

## **10. Data model**

**Eight tables plus one configuration file cover the MVP. Every entry table has the same lifecycle columns: `status` (`draft` | `submitted`), `created_at`, `updated_at`, `deleted_at`, `delete_reason`. All times are stored in UTC and shown in WIB.**

| **Entity** | **Fields** | **Relationships and constraints** |
| --- | --- | --- |
| `**admin_user**` | **id, username (unique), password\_hash (bcrypt/argon2id), display\_name, failed\_attempts, locked\_until, password\_changed\_at, last\_login\_at** | **Exactly one row. Created by a server command, not the UI.** |
| `**temperature_entry**` | **id, entry\_no, period\_month (first day of month), captured\_at, location\_code, avg\_temp DECIMAL(4,1), temp\_status, humidity DECIMAL(4,1) NULL, humidity\_status NULL, thresholds\_json, ac\_notes** | **Unique (location\_code, period\_month) among submitted, non-deleted rows. 1–N `attachment`.** |
| `**cctv_entry**` | **id, entry\_no, inspected\_at, shift, total\_cameras\_snapshot, is\_nihil, broken\_count, availability\_pct DECIMAL(5,1), ntp\_status, ntp\_note, nvr\_status, retention\_days NULL, nvr\_note, handover\_note, entry\_status, thresholds\_json** | **1–N `cctv_broken_camera`; 0–5 `attachment`. is\_nihil = true ⇒ 0 broken rows.** |
| `**cctv_broken_camera**` | **id, cctv\_entry\_id, camera\_name, ip\_address, zone\_code, issue\_type, note** | **N–1 `cctv_entry`, deleted with its entry.** |
| `**cleardesk_session**` | **id, session\_no, started\_at, shift, notes, row\_count, compliant\_count, violation\_count** | **1–N `cleardesk_row` (≥ 1 when submitted). Counts are recalculated on every save.** |
| `**cleardesk_row**` | **id, session\_id, position, mac\_address CHAR(17), location\_unit, ip\_address, os, os\_other, compliance (`patuh` / `temuan`), finding\_categories (JSON array), finding\_note, follow\_up, photo\_attachment\_id NULL** | **N–1 session. Index on mac\_address for auto-fill (FR-D3). Unique (session\_id, mac\_address).** |
| `**attachment**` | **id, owner\_type, owner\_id, original\_name, stored\_name (UUID), mime\_type, size\_bytes, sha256, uploaded\_at, deleted\_at** | **Polymorphic owner: temperature entry, CCTV entry or Clear Desk row. File lives on disk under `storage/app/private/<yyyy>/<mm>/`.** |
| `**change_log**` | **id, occurred\_at, action (`create` / `update` / `delete` / `export` / `login` / `login_failed` / `logout` / `password_change`), entity\_type, entity\_id, entry\_no, changes JSON (`[{field, old, new}]`), reason, meta JSON (filters, format, row count), ip\_address, user\_agent** | **Append-only: the application has no update or delete path for this table.** |

**Configuration (file, version-controlled; change = redeploy)**

- **Temperature sensor locations (code, name, sensor ID) — 4 entries.**
- **Temperature and humidity bands; CCTV availability threshold (90%); Clear Desk compliance target (95%).**
- **Total cameras (e.g. 68) and CCTV zone list.**
- **Shift hours; time zone `Asia/Jakarta`.**
- **Report header text, logo path, Head of IT name for the signature block.**
- **Module release dates (for the "Baru" badge).**

**Volume estimate (Assumption).**** Per year: 48 temperature entries, about 1,100 CCTV entries (3 per day), and up to 36,500 Clear Desk rows (≤ 100 per day). At an average 2 MB per attachment, files grow by roughly 2–5 GB per year. Database size stays well under 1 GB for 5 years.**

## **11. Authentication and security**

**One account, one login, every page behind it. Controls are sized for hospital-internal data under ISO 27001 and Indonesia's Personal Data Protection Law (UU PDP No. 27/2022); the data is IT operational data, though notes and photos may incidentally show personal or patient information.**

| **ID** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- |
| **SEC-1** | **Login**** with username + password. Every route except `/login` requires an authenticated session.** | **Requesting `/rekap/suhu` while logged out redirects to `/login`; after login, the admin lands on the originally requested page.** |
| **SEC-2** | **Generic error.**** A failed login says "Username atau password salah" for both unknown user and wrong password.** | **Both cases show the identical message.** |
| **SEC-3** | **Rate limit.**** 5 failed attempts within 15 minutes lock login for 15 minutes for that username and IP.** | **6th attempt within 15 min shows "Terlalu banyak percobaan. Coba lagi dalam 15 menit." and is refused even with the right password.** |
| **SEC-4** | **Password storage**** with argon2id or bcrypt (cost ≥ 12). Never logged, never shown.** | **The database holds only a hash; grep of logs finds no password.** |
| **SEC-5** | **Change password**** (Account menu): current password, new password, confirmation. New password: ≥ 12 characters, not equal to the username, not equal to the current password. Other sessions are ended after a change.** | **A 10-character password is refused; a valid change logs the admin out on other devices and writes a `password_change` log record.** |
| **SEC-6** | **Password reset**** only on the server via a CLI command (e.g. `php artisan admin:reset-password`), which writes a log record.** | **Running the command sets a new password; the next UI login works with it.** |
| **SEC-7** | **Session.**** Idle timeout 30 minutes; absolute timeout 12 hours. Cookie flags Secure, HttpOnly, SameSite=Lax. Session ID regenerated at login.** | **After 31 idle minutes any action returns to login. A form draft typed before timeout is kept locally and offered back after re-login (NFR-6).** |
| **SEC-8** | **Transport.**** HTTPS only (HTTP redirects to HTTPS), TLS 1.2+, HSTS. Certificate from the hospital's internal CA or a valid public certificate.** | `**http://` redirects with 301; a TLS 1.0 handshake fails.** |
| **SEC-9** | **Network.**** Server reachable only from the hospital LAN and VPN address ranges (firewall rule).** | **A request from outside those ranges gets no response.** |
| **SEC-10** | **Web protections****: CSRF token on every form, output escaping, parameterised queries, Content-Security-Policy without inline scripts, X-Frame-Options DENY.** | **An OWASP ZAP baseline scan reports no High or Medium findings before go-live.** |
| **SEC-11** | **Uploads**** per FR-F11; files never executed or served from a public path; downloads use `Content-Disposition: attachment` except images and PDFs, which open inline.** | **Requesting a file URL without a session returns 401/redirect.** |
| **SEC-12** | **Change log**** (FR-F8, FR-F9, FR-E13, SEC-5) also records login, failed login and logout with IP and user agent. A read-only "Riwayat aktivitas" page lists it with date and action filters.** | **Each listed action produces exactly one record; the page offers no edit or delete.** |
| **SEC-13** | **Secrets**** (database password, app key) live in an environment file readable only by the app's OS user, never in the code repository.** | **Repository scan finds no credentials.** |

## **12. Non-functional requirements**

| **ID** | **Area** | **Requirement** | **Acceptance criteria** |
| --- | --- | --- | --- |
| **NFR-1** | **Performance** | **Pages load in ≤ 2 s (p95) on the hospital LAN; recap with 25 rows and filters in ≤ 1 s server time on 5 years of data.** | **Load test with 200,000 Clear Desk rows: recap p95 ≤ 1 s.** |
| **NFR-2** | **Performance** | **Excel export of 10,000 rows in ≤ 15 s; PDF of 500 rows in ≤ 20 s.** | **Timed on the production VM.** |
| **NFR-3** | **Availability** | **≥ 99% during 06:00–23:00 WIB per month. Planned maintenance outside those hours.** | **Uptime monitor (e.g. Uptime Kuma) reports ≥ 99%.** |
| **NFR-4** | **Backup** | **Nightly at 01:00 WIB: full database dump + incremental copy of uploaded files, compressed and encrypted. Keep the last 30 daily backups.** | **30 backup sets exist after 30 days; the 31st night removes the oldest.** |
| **NFR-5** | **Backup location** | **Backups are copied to a ****different machine or NAS**** than the app VM. *Assumption (challenges the default): backups on the same VM are lost with it.* A monthly restore test to a test VM is recorded.** | **Restore of last night's backup on a clean VM yields a working app with all entries and files; recovery point ≤ 24 h, recovery time ≤ 4 h.** |
| **NFR-6** | **Data safety** | **Forms also keep unsent input in browser storage, restored on reopen after timeout, crash or lost Wi-Fi. Cleared on successful submit.** | **Turn off Wi-Fi mid-form, reload after reconnecting: input is offered back.** |
| **NFR-7** | **Retention** | **All entries, attachments and logs are kept indefinitely. No automatic purge.** | **No scheduled job deletes data.** |
| **NFR-8** | **Devices** | **Works on current Chrome, Edge, Firefox and Safari (last 2 versions) on desktop (≥ 1280 px), tablet (768–1279 px) and phone (360–430 px).** | **Every page passes a manual check at 390 px with no horizontal page scroll.** |
| **NFR-9** | **Accessibility** | **WCAG 2.1 AA for contrast (text ≥ 4.5:1), keyboard use, labels on every input, visible focus. Touch targets ≥ 44 × 44 px. Status never shown by colour alone.** | **axe-core reports 0 serious/critical issues on every page.** |
| **NFR-10** | **Localisation** | **Bahasa Indonesia UI; WIB time; Indonesian number format in display.** | **See FR-F12.** |
| **NFR-11** | **Auditability** | **Change log append-only and included in backups.** | **Attempting an update on `change_log` via the app is impossible (no route).** |
| **NFR-12** | **Maintainability** | **One code base, one CSS build, automated tests for status logic, formulas and exports; README with install, backup and restore steps.** | **Test suite covers every test value in FR-T2, FR-T3, FR-C2, FR-D5 and passes in CI.** |
| **NFR-13** | **Monitoring** | **Application errors logged to file with daily rotation (keep 90 days); disk-space alert at 80%.** | **A forced error appears in the log with timestamp and stack trace.** |

## **13. UI/UX notes based on the prototype**

**Keep the prototype's shell and visual language; fix the inconsistencies found in the design review before building. The review canvas ("RS Awal Bros Portal — Design Analysis") holds screenshots and the full list.**

**Keep**

- **Sticky white navbar with the red RS AWAL BROS wordmark; one solid teal page header per screen; white cards on a light grey page.**
- **Token set from `theme.css` (teal 900 `#0B3B33`, teal 700 `#0F6B5C` primary, ink `#17262A`, muted `#637377`, ok / warn / error colours) and IBM Plex Sans.**
- **Two-column form layout on desktop (form left, context panel right), single column on phones.**
- **Radio cards for sensor location and NTP status; drag-and-drop upload area.**

**Change**

| **Prototype** | **Product** |
| --- | --- |
| **Temperature target 15–22 °C next to legend 18–24 °C** | **One band set from configuration on every screen (FR-T2)** |
| **LIVE / NEW badge on every portal card; "LIVE IoT" / "LIVE NVR" panels** | **No LIVE badges; panels show real saved data (FR-P5, FR-T6, FR-C5)** |
| **Dashboard and HIS cards duplicated; cards linking to `#**` | **De-duplicated; external systems marked as external links; unbuilt modules not shown** |
| **Users page; Password as a top-nav item** | **No Users page; "Akun" menu with Ubah password and Keluar** |
| **Five equal buttons in the form footer, including "Clear form"** | **Footer = Simpan draft (secondary) + Kirim (primary). Reset is a text link with confirmation. Back/recap links live in the header** |
| **Export buttons in green, red and blue** | **One secondary "Ekspor" button with a menu: Excel, PDF, Cetak** |
| **Primary button mint on one page, teal on others** | **One primary style: teal 700, white text** |
| **Breadcrumb separators `/`, `>`, chevrons** | **One breadcrumb component, chevron separator** |
| **Mixed English ("Sign In", "Add file", "Clear form")** | **Bahasa Indonesia everywhere (FR-F12)** |
| **Petugas IT and Verified columns in recaps** | **Removed (single user, no in-app verification)** |
| **Rekap header clips its CTA at 390 px; login shows a full blank screen on phones** | **Header wraps and CTA goes full width below 768 px; login shows logo + form only on phones** |
| **Login hero image hot-linked from an external host** | **Image stored in the app's own assets** |

**Mobile-first form details (admin walks while entering)**

- **Inputs ≥ 44 px tall; numeric keypad for temperature and humidity (`inputmode="decimal"`); IP and MAC fields use `inputmode="text"` with auto-uppercase for MAC.**
- **On phones, Kirim and "Simpan & tambah berikutnya" sit in a sticky bottom bar.**
- **Clear Desk rows collapse to a one-line summary (MAC · lokasi · status) once saved; tap to expand.**
- **Camera upload accepts the phone camera directly (`capture` attribute) for evidence photos.**

## **14. Technical recommendations**

**Recommendation: a server-rendered Laravel monolith with MySQL on one on-premise Linux VM.**** It is the simplest stack that covers every requirement out of the box, and it is the easiest to staff in Indonesia, where PHP/Laravel developers are widely available and affordable.**

**Why this fits a small team**

- **One deployable, one language.**** Pages, forms, validation, exports and the change log live in one PHP code base. No separate front-end build pipeline or API to maintain.**
- **Built-in features map to requirements****: authentication and session handling (SEC-1, SEC-7), login rate limiting (SEC-3), password hashing (SEC-4), CSRF protection (SEC-10), form validation (6.x), database migrations, scheduled jobs (NFR-4) and file storage (FR-F11).**
- **The prototype is already Tailwind HTML****, so its markup moves into Blade templates with little rework.**
- **Cheap to host****: runs on a modest VM with no licence costs.**

| **Layer** | **Choice** | **Notes** |
| --- | --- | --- |
| **Language / framework** | **PHP 8.3+, Laravel (current supported major, 12.x or newer)** | **Long-term support, large local talent pool** |
| **UI** | **Blade templates + Tailwind CSS (single Vite build) + Alpine.js** | **Alpine handles dynamic Clear Desk rows, live status preview, MAC auto-fill calls** |
| **Database** | **MySQL 8.0 (or MariaDB 10.11 LTS)** | **JSON columns for thresholds, findings and change sets** |
| **Excel export** | `**maatwebsite/excel` (PhpSpreadsheet)** | **Real typed cells, frozen header, auto-filter** |
| **PDF / print** | `**barryvdh/laravel-dompdf` rendering the same Blade template used by the print view** | **One layout for PDF and print (FR-E11). If letterhead fidelity proves insufficient, switch to `spatie/laravel-pdf` with headless Chromium** |
| **Change log** | **Model observers writing to `change_log` (or `spatie/laravel-activitylog`)** | **Append-only table** |
| **Backup** | `**spatie/laravel-backup` + cron, target = NAS over SFTP/SMB** | **Encrypted archives, 30-set retention** |
| **Web server** | **Nginx + PHP-FPM** | **HTTPS with internal CA certificate** |
| **OS / VM** | **Ubuntu Server 24.04 LTS; 2 vCPU, 4 GB RAM, 100 GB disk** | **Disk alert at 80% (NFR-13)** |
| **Monitoring** | **Laravel log + Uptime Kuma (self-hosted)** | **Availability metric (NFR-3)** |

**Alternatives considered**

| **Option** | **Why not first choice** |
| --- | --- |
| **Django (Python)** | **Equally capable; slightly smaller local hiring pool for web work. Good choice if the team already knows Python.** |
| **Next.js + Node API + Prisma** | **Two layers (front end + API) to build and secure; more moving parts than this app needs.** |
| **ASP.NET Core** | **Strong fit if the hospital already runs Windows Server and .NET; higher hosting cost on Windows.** |
| **Low-code (e.g. AppSheet, Google Forms + Sheets)** | **Data would leave the hospital network; weak audit trail; export layout control is limited.** |

&#91;embedded content: Deployment architecture · one VM, LAN/VPN only, off-machine backup\]

**The admin's browser reaches the VM only through the hospital LAN or VPN; the app, database and uploaded files share one VM, and the nightly backup leaves it for a separate machine.**

## **15. Assumptions, risks and open questions**

**Assumptions**** (each is used in a requirement and can be changed by one answer)**

| **ID** | **Assumption** | **Used in** |
| --- | --- | --- |
| **A-1** | **Temperature bands: Normal 18.0–24.0 °C; Peringatan 24.1–26.9 °C or < 18.0 °C; Kritis ≥ 27.0 °C — pending hospital SOP confirmation** | **FR-T2** |
| **A-2** | **Humidity Normal 45.0–60.0 % RH, otherwise Peringatan** | **FR-T3** |
| **A-3** | **Shift hours Pagi 07:00–13:59, Siang 14:00–20:59, Malam 21:00–06:59 (from the prototype)** | **FR-F6** |
| **A-4** | **"Today" = calendar date (WIB) of the inspection time, so a 01:30 night-shift check counts for the new day** | **Section 7** |
| **A-5** | **Broken cameras are structured rows (name, IP, zone, issue), not free text, so counts and zone filters work** | **6.3** |
| **A-6** | **The hospital provides the CCTV zone list and total camera count** | **6.3, config** |
| **A-7** | **CCTV is Kritis below 90% availability; Clear Desk target is 95% compliance** | **FR-C2, FR-D5** |
| **A-8** | **One Excel workbook per recap export (data + summary sheets); cross-module export is Phase 2** | **Section 9** |
| **A-9** | **Backups are stored on a separate machine or NAS, not only on the app VM** | **NFR-5** |
| **A-10** | **Volume ≤ 100 Clear Desk rows per day; average attachment 2 MB** | **Section 10** |
| **A-11** | **Admin is the only person who ever logs in; the Head of IT only receives printed or PDF reports** | **Sections 4, 11** |

**Risks**

| **Risk** | **Impact** | **Likelihood** | **Mitigation** |
| --- | --- | --- | --- |
| **Single admin account is shared or its password leaks** | **No way to tell who entered data; audit evidence weakened** | **Medium** | **Strong password rule, rate limit, change log with IP/device, network restriction; multi-user is a Phase 3 option** |
| **Admin absent (leave, sickness)** | **Checks not recorded; daily completeness drops** | **Medium** | **Documented handover: a deputy uses the same account under a written procedure, or add a second account in Phase 2** |
| **Threshold values differ from SOP** | **Wrong statuses in audit reports** | **Medium** | **Confirm A-1 before go-live; thresholds snapshot per entry (FR-T5)** |
| **Backups on the same VM or never tested** | **Total data loss** | **Low–Medium** | **NFR-5: off-machine copies, monthly restore test** |
| **Unreliable Wi-Fi while walking** | **Lost form input** | **Medium** | **NFR-6 local draft recovery; "Save and add next" saves server-side per row** |
| **Official report format required by auditors differs from default** | **Rework of PDF layout** | **Medium** | **Obtain template before build of exports (Q-1)** |
| **Small team without Laravel experience** | **Slower delivery, security mistakes** | **Unknown** | **Use framework defaults, OWASP ZAP scan (SEC-10), code review by an external PHP developer before go-live** |
| **Photos or notes capture patient or staff personal data** | **Privacy exposure under UU PDP** | **Low** | **Guidance on the form not to photograph screens with patient data; files private and access-logged** |

**Open questions**

| **ID** | **Question** | **Owner** | **Needed by** |
| --- | --- | --- | --- |
| **Q-1** | **Is there an official report template (letterhead, form code, document number) for ISO 27001 / KARS evidence? Please share it.** | **Admin / Head of IT** | **Before export build (Phase 1, sprint 3)** |
| **Q-2** | **Confirm temperature and humidity bands against the hospital SOP (A-1, A-2).** | **Head of IT** | **Before go-live** |
| **Q-3** | **Provide the CCTV zone list and total camera count (A-6).** | **Admin** | **Before CCTV build** |
| **Q-4** | **Name (and NIP, if required) of the Kepala Instalasi IT for the signature block.** | **Admin** | **Before export build** |
| **Q-5** | **Should a deleted entry be restorable from the UI, or only by IT on the database? (MVP: database only.)** | **Admin** | **Phase 1 review** |
| **Q-6** | **Is a NAS or second server available for backups (A-9)?** | **IT infrastructure** | **Before go-live** |
| **Q-7** | **Who builds and maintains the app (in-house or vendor), and what PHP experience do they have?** | **Head of IT** | **Before kickoff** |
| **Q-8** | **Baseline: how long do today's separate forms take per day? Needed to show time saved.** | **Admin** | **Before go-live (measure 1 week)** |
| **Q-9** | **Hospital logo file (SVG or high-resolution PNG) for reports and login.** | **Admin** | **Before export build** |

## **16. Phased roadmap and MVP scope**

**The MVP (Phase 1) ships the three prototype modules end to end. Durations assume 1–2 developers and are estimates (Assumption), to be re-planned once Q-7 is answered.**

| **Phase** | **Scope** | **Estimated duration** | **Exit gate** |
| --- | --- | --- | --- |
| **0 — Preparation** | **Answer Q-1 to Q-4, Q-6, Q-7, Q-9; provision VM and backup target; fix prototype UI issues (section 13)** | **1–2 weeks** | **All Phase 1 blocking questions answered; VM reachable over HTTPS from LAN and VPN** |
| **1 — MVP** | **Login, change password, Account menu; dashboard progress (section 7); Temperature, CCTV and Clear Desk forms + recaps; drafts, edit, soft delete, change log; Excel, PDF, print; backups; activity log page** | **8–10 weeks** | **Every acceptance criterion in sections 6–12 passes; OWASP ZAP baseline clean; restore test passed; 2 weeks of parallel run with the old forms** |
| **2 — More modules + controls** | **Hardware Server IT monitoring; User Access Conformity (HIS); BPJS Online Queue check (manual, per shift); period closing / locking; per-shift completion; Settings page for thresholds and lists; camera and workstation master lists; "Ekspor semua modul" workbook; restore of deleted entries** | **8–12 weeks** | **Each new module meets the same Form + Rekap + export pattern and its own acceptance criteria** |
| **3 — Optional** | **Second account or roles (deputy, read-only Head of IT); notifications (email / WhatsApp) for reminders and Kritis entries; trend charts per module** | **To be scoped** | **Business case approved by Head of IT** |

**MVP in / out at a glance**

- **In:**** 3 modules, single admin, drafts, edit, soft delete, change log, dashboard progress, 25th reminder, filters and search, Excel / PDF / print, mobile-ready forms, nightly off-machine backups.**
- **Out:**** approval workflow, period closing, Settings UI, master lists, notifications, multi-user, other modules.**

## **17. Acceptance criteria**

**The MVP is accepted when every requirement's own criteria (FR-, SEC-, NFR- tables above) pass and the end-to-end scenarios below pass on the production VM, on one desktop and one phone.**

**End-to-end scenarios (user acceptance test)**

| **#** | **Scenario (Given / When / Then)** | **Covers** |
| --- | --- | --- |
| **E2E-1** | **Given**** a fresh install, ****when**** the admin logs in with the CLI-created password and changes it, ****then**** the old password no longer works and the change log shows `login` and `password_change`.** | **SEC-1, SEC-5, SEC-6** |
| **E2E-2** | **Given**** no entries today, ****when**** the admin submits one CCTV check on a phone with 2 broken cameras (68 total), ****then**** the dashboard shows "1 dari 2", the CCTV recap shows working 66, availability 97.1%, status Peringatan.** | **FR-C1, FR-C2, FR-P1** |
| **E2E-3** | **Given**** a Clear Desk session, ****when**** the admin adds 12 rows with "Simpan & tambah berikutnya" (one MAC seen before), loses Wi-Fi after row 8 and reconnects, ****then**** all rows are kept, the known MAC was auto-filled, and after submit the dashboard shows "2 dari 2".** | **FR-D1–D4, NFR-6** |
| **E2E-4** | **Given**** October with 3 temperature entries, ****when**** the date is 25 Oct, ****then**** the reminder banner shows; ****when**** the 4th location is submitted with 27.0 °C, ****then**** the banner disappears and the entry is Kritis in the recap and in "Latest exceptions".** | **FR-P2, FR-P3, FR-P6, FR-T2** |
| **E2E-5** | **Given**** a submitted entry, ****when**** the admin edits the temperature from 24.0 to 24.5 and later deletes it with a reason, ****then**** the recap no longer shows it, the totals update, and the entry's history lists both actions with old/new values and the reason.** | **FR-F8, FR-F9, SEC-12** |
| **E2E-6** | **Given**** a Clear Desk recap filtered to October and status Temuan, ****when**** the admin exports Excel and PDF and prints, ****then**** all three contain exactly the filtered rows, the PDF is A4 landscape with logo, statistics and an unsplit signature block, and the change log has 2 export records.** | **FR-E1–E13** |
| **E2E-7** | **Given**** a laptop outside the hospital network without VPN, ****when**** it requests the portal URL, ****then**** there is no response.** | **SEC-9** |
| **E2E-8** | **Given**** last night's backup, ****when**** it is restored to a clean VM, ****then**** the app runs with all entries, attachments and change-log records up to the backup time.** | **NFR-4, NFR-5** |

**Definition of done (every requirement)**

- [ ] **Acceptance criteria pass and are covered by an automated test where the logic is computed (status, formulas, exports, permissions).**
- [ ] **All UI text in Bahasa Indonesia; screens checked at 1440 px and 390 px.**
- [ ] **axe-core: 0 serious or critical issues on the changed pages.**
- [ ] **Every data change and export writes a change-log record.**
- [ ] **README updated (configuration keys, install, backup, restore).**


