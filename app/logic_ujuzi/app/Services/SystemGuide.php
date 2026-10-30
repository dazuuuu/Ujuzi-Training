<?php

namespace App\Services;

/**
 * The Super Admin's guide to the whole system. Each section has a title,
 * keywords (extra words people may ask with), where to find it, and the
 * answer as short paragraphs. The help page searches these to answer
 * questions; keep wording plain and add a section when a feature is added.
 */
class SystemGuide
{
    public static function sections(): array
    {
        return [
            [
                'id' => 'overview',
                'title' => 'How the system fits together',
                'keywords' => 'overview roles who portals start beginning structure',
                'where' => 'Everything',
                'body' => [
                    'There are seven kinds of user: Super Admin (you, plus any limited admins), students, tutors, organisations providing courses, their branch admins, organisations providing attachment, and their branch admins.',
                    'Organisations providing courses create branches, categories and courses (through their tutors). Students enrol in courses, pay from their wallet, learn module by module, sit the final exam and get a certificate.',
                    'Students then request attachment at an organisation providing attachment. That organisation (or its branch admin) accepts, follows the student, completes the attachment and can write a recommendation letter.',
                    'You approve courses, handle delete and detail-change requests, watch finances and download reports.',
                ],
            ],
            [
                'id' => 'organisations',
                'title' => 'Adding an organisation',
                'keywords' => 'register organisation invite link create new company school institution',
                'where' => 'Organisations → Create, then Share registration',
                'body' => [
                    'Organisations do not sign up on their own. Create the organisation under Organisations, then generate a one-time registration link (or email it) from its edit page. The organisation head opens the link and creates their admin account.',
                    'On the same page you can give the organisation a course-code range; codes from it are offered to tutors as suggestions.',
                ],
            ],
            [
                'id' => 'users',
                'title' => 'Users, students and accounts',
                'keywords' => 'user account student tutor find edit block suspend unlock password reset registration number',
                'where' => 'Registered users, Students, Attachment providers, Organisations providing courses',
                'body' => [
                    'Registered users lists everyone; the Students and provider menu items are the same list filtered by role. Open a user to see their details, edit, block, suspend or unlock them.',
                    'Every student gets a registration number automatically (e.g. UJ0012709/26).',
                    'Student lookup finds a student by name, registration number, phone or certificate number.',
                ],
            ],
            [
                'id' => 'security',
                'title' => 'Account security: one account per person',
                'keywords' => 'security duplicate same name id birth certificate email phone already exists login twice device session locked',
                'where' => 'Automatic; requests under Detail change requests',
                'body' => [
                    'No two accounts may share the same full name (first, last and other names all matching), email address, phone number, or National ID / passport / birth certificate number. Anyone who tries is told "An account already exists in the system".',
                    'An account can be signed in on one device at a time. Signing in on a new device signs the other device out on its next click, with a message saying why. This applies to admins too.',
                    'Accounts that shared an email or phone before this rule are locked until they give their own details; unlock one from its user page if needed.',
                ],
            ],
            [
                'id' => 'change-requests',
                'title' => 'Changing names, email or ID number',
                'keywords' => 'change request correct name misspelt email id number approve update credentials details',
                'where' => 'Detail change requests',
                'body' => [
                    'Once saved, a person\'s names, sign-in email and ID / birth certificate number are locked. They correct them from Profile → Request a change, giving a reason.',
                    'The request shows under Detail change requests with the old and new values. Approve to apply it (refused automatically if another account already uses the new value) or decline with a note. The person is emailed either way.',
                ],
            ],
            [
                'id' => 'courses',
                'title' => 'Courses, course codes and approval',
                'keywords' => 'course create approve publish code module quiz final exam pass mark tutor',
                'where' => 'Course approvals',
                'body' => [
                    'Tutors create courses from their portal. When creating a course the tutor types a course code (e.g. CS101) — letters, numbers or dashes, unique in the whole system. Students can type the code in search to find the course. Codes are never printed on certificates.',
                    'Every new course waits in Course approvals until you approve it; preview it there first. A rejected course goes back to you once the tutor edits it.',
                    'A course has modules (video, resources, optional quiz) and a final exam with a pass mark and duration.',
                ],
            ],
            [
                'id' => 'payments',
                'title' => 'Wallets, fees and finance',
                'keywords' => 'money payment wallet deposit fee balance paid finance rate minimum coins mpesa outstanding',
                'where' => 'Finance',
                'body' => [
                    'Students top up a wallet and pay course fees from it. A student must pay at least the minimum percentage of a course\'s fee (set under Finance) to start it and to request attachment.',
                    'Finance shows fees due, collected and outstanding per organisation; open an organisation for its detail. The coin rate and minimum payment are set there.',
                    'In reports, finance status is Paid (fee fully paid), Partly paid, or Not paid.',
                ],
            ],
            [
                'id' => 'attachments',
                'title' => 'Attachments',
                'keywords' => 'attachment attachee provider request accept complete recommend letter branch placement internship',
                'where' => 'Attachments, Attachment payments',
                'body' => [
                    'A student requests one attachment per course at one branch of an organisation providing attachment. The branch accepts, puts on hold, completes or rejects it, and can recommend the student (which creates a recommendation letter).',
                    'Statuses: Requested (waiting), Accepted / Ongoing, Completed (or Recommended), Rejected.',
                    'Attachments lists every request with filters and an Excel download.',
                ],
            ],
            [
                'id' => 'attachment-payments',
                'title' => 'Attachment payments (completed and ongoing)',
                'keywords' => 'attachment payments completed ongoing status pay provider list print',
                'where' => 'Attachments → Attachment payments',
                'body' => [
                    'The payments list shows attachees per branch with a Status column. Use the Status filter to show Completed, Ongoing, or both. Filter by organisation, branch and dates, then view/print the PDF or download Excel.',
                    'For completed attachments the dates filter the completion date; for ongoing ones, the date they were accepted.',
                    'Organisations providing attachment and their branch admins see the same list for their own branches.',
                ],
            ],
            [
                'id' => 'reports',
                'title' => 'Reports and organisation activity',
                'keywords' => 'report download excel pdf export filter organisation branch completed accepted status finance activity date time',
                'where' => 'Reports → Organisation activity',
                'body' => [
                    'Organisation activity is the report to see what an organisation and its branches did. Choose the kind of organisation (providing courses or attachment), one organisation or all, one branch or all, a status (e.g. Completed, Ongoing, Accepted), a finance status (Paid, Partly paid, Not paid) and dates.',
                    'For organisations providing courses each row is a student\'s enrolment with the date and time enrolled, progress, completion date and time, fee, paid, balance and finance status. For organisations providing attachment each row is a request with requested, accepted and completed dates and times, and the student\'s fees.',
                    'Counts at the top separate completed, ongoing/accepted and waiting students and the money. Excel and PDF downloads contain exactly the filtered rows.',
                    'The other report tabs (students, enrolments, finances, deposits, …) and Charts remain for whole-system views.',
                ],
            ],
            [
                'id' => 'certificates',
                'title' => 'Certificates and documents',
                'keywords' => 'certificate template number verify document letter layout generate',
                'where' => 'Documents, Certificates',
                'body' => [
                    'Students who pass a course\'s final exam earn a certificate with its own number, which anyone can verify on the public Student lookup page.',
                    'Documents sets the certificate and letter layouts; Certificates lists every certificate issued and can generate batches.',
                ],
            ],
            [
                'id' => 'navigation',
                'title' => 'Renaming and reordering menus',
                'keywords' => 'navigation menu rename names reorder position sidebar tab public menu links',
                'where' => 'Navigation',
                'body' => [
                    'Navigation edits every menu: your own Super Admin menu, the Students, Tutors, both kinds of organisation and both kinds of branch admin portals, and the public website menu.',
                    'Pick a menu at the top, type new names into the boxes and drag ⠿ to reorder, then Save. Clear a name to return to its original. Changes apply to everyone at once.',
                    'For the public website menu you can also add, remove and point links to any page.',
                ],
            ],
            [
                'id' => 'pages',
                'title' => 'Public website pages and look',
                'keywords' => 'website homepage public page about contact theme colour logo featured courses footer',
                'where' => 'Public pages, Logos & featured courses, Settings',
                'body' => [
                    'Public pages edits the homepage, About, Contact and custom pages section by section, plus the navbar and footer. Theme changes colours.',
                    'Logos & featured courses picks partner logos and the courses shown on the homepage. The site name and logo are under Settings.',
                ],
            ],
            [
                'id' => 'forms',
                'title' => 'Registration and profile forms',
                'keywords' => 'form field registration profile question required id document gender',
                'where' => 'Forms',
                'body' => [
                    'Forms decides what each role fills in at registration and on their profile, and the course form tutors use. Add fields, mark them required and assign forms to roles.',
                ],
            ],
            [
                'id' => 'deletions',
                'title' => 'Deleting things safely',
                'keywords' => 'delete remove request branch course cleanup data reset',
                'where' => 'Delete requests, Data Cleanup',
                'body' => [
                    'Organisations cannot delete courses or branches directly; they send a delete request that you approve under Delete requests.',
                    'Data Cleanup removes test data. Use it with care: deletions cannot be undone.',
                ],
            ],
            [
                'id' => 'admins',
                'title' => 'Other admins and their access',
                'keywords' => 'admin add access permission limited owner',
                'where' => 'Admins',
                'body' => [
                    'Owners can add admins and tick the sections each may open. Limited admins only see those sections in their menu and cannot open other pages by typing their address.',
                ],
            ],
            [
                'id' => 'updates',
                'title' => 'Installing updates',
                'keywords' => 'update upgrade install migration new version red button what changed',
                'where' => 'Updates (red Update button in the top bar)',
                'body' => [
                    'When new features are uploaded, a red Update button appears in the top bar. Open Updates to read what each update does, then press Install updates.',
                    'After installing you see "What\'s new" — the changes and what you can now do. History lists every update installed.',
                ],
            ],
            [
                'id' => 'settings',
                'title' => 'Settings and email',
                'keywords' => 'settings email smtp mail name logo test',
                'where' => 'Settings',
                'body' => [
                    'Settings holds the site name, logo, email (SMTP) details and other options. Use Send test email to check mail works.',
                ],
            ],
        ];
    }
}
