<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CallLog;
use App\Models\Carrier;
use App\Models\DialerContact;
use App\Models\DialerContactActivity;
use App\Models\DialerContactComment;
use App\Models\InboundDid;
use App\Models\Role;
use App\Models\SipCredential;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class QaTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSipUsers();
        $this->seedCarriersAndDids();
        $this->seedContactsWithHistory();
        $this->seedCallLogs();
    }

    private function seedSipUsers(): void
    {
        $role = Role::where("name", "superadmin")->first();
        if (!$role) {
            $role = Role::create(["name" => "superadmin"]);
        }

        $admin = User::firstOrCreate(
            ["email" => "admin@webphone.local"],
            [
                "external_name" => "QA Admin",
                "internal_name" => "qa-admin",
                "password" => Hash::make("AdminPass123!"),
            ]
        );

        $agents = [
            ["external_name" => "Agent Smith", "internal_name" => "agent-smith", "email" => "agent1@webphone.local", "sip_username" => "1001"],
            ["external_name" => "Agent Johnson", "internal_name" => "agent-johnson", "email" => "agent2@webphone.local", "sip_username" => "1002"],
            ["external_name" => "Agent Williams", "internal_name" => "agent-williams", "email" => "agent3@webphone.local", "sip_username" => "1003"],
        ];

        foreach ($agents as $agentData) {
            $user = User::firstOrCreate(
                ["email" => $agentData["email"]],
                [
                    "external_name" => $agentData["external_name"],
                    "internal_name" => $agentData["internal_name"],
                    "password" => Hash::make("AgentPass123!"),
                ]
            );

            SipCredential::firstOrCreate(
                ["user_id" => $user->id],
                [
                    "sip_username" => $agentData["sip_username"],
                    "sip_password" => "sip" . $agentData["sip_username"],
                ]
            );
        }
    }

    private function seedCarriersAndDids(): void
    {
        $carrier = Carrier::firstOrCreate(
            ["name" => "Test Carrier"],
            [
                "sip_domain" => "sip.testcarrier.com",
                "sip_port" => 5060,
                "transport" => "udp",
            ]
        );

        $dids = ["+15551234567", "+15559876543", "+15551112222"];
        foreach ($dids as $did) {
            InboundDid::firstOrCreate(
                ["did" => $did],
                [
                    "carrier_id" => $carrier->id,
                    "label" => "QA Test DID " . $did,
                ]
            );
        }
    }

    private function seedContactsWithHistory(): void
    {
        $user = User::where("email", "admin@webphone.local")->first();

        $contacts = [
            [
                "name" => "John Doe",
                "company" => "Acme Corp",
                "phone" => "+15551234567",
                "email" => "john.doe@acme.com",
                "is_flagged" => true,
                "labels" => ["VIP", "Enterprise"],
                "comments" => [
                    "Called regarding enterprise pricing. Interested in 50-seat package.",
                    "Follow-up scheduled for next week. Sent proposal via email.",
                ],
                "activities" => [
                    ["action" => "created", "description" => "Contact created"],
                    ["action" => "called", "description" => "Outbound call - 5 min"],
                    ["action" => "updated", "description" => "Updated contact info"],
                ],
            ],
            [
                "name" => "Jane Smith",
                "company" => "TechStart Inc",
                "phone" => "+15559876543",
                "email" => "jane.smith@techstart.io",
                "is_flagged" => false,
                "labels" => ["Startup", "Hot Lead"],
                "comments" => [
                    "Initial contact. Very interested in our solution.",
                ],
                "activities" => [
                    ["action" => "created", "description" => "Contact created from web form"],
                    ["action" => "emailed", "description" => "Sent welcome email"],
                ],
            ],
            [
                "name" => "Bob Wilson",
                "company" => "Global Solutions",
                "phone" => "+15551112222",
                "email" => "bob.wilson@globalsol.com",
                "is_flagged" => true,
                "labels" => ["International"],
                "comments" => [
                    "Needs international calling features.",
                    "Discussed custom integration requirements.",
                    "Demo scheduled for Friday.",
                ],
                "activities" => [
                    ["action" => "created", "description" => "Contact created"],
                    ["action" => "called", "description" => "Inbound call - 12 min"],
                    ["action" => "called", "description" => "Outbound follow-up - 8 min"],
                    ["action" => "updated", "description" => "Updated to VIP status"],
                ],
            ],
            [
                "name" => "Alice Brown",
                "company" => "Design Studio",
                "phone" => "+15552223333",
                "email" => "alice@designstudio.co",
                "is_flagged" => false,
                "labels" => ["Creative"],
                "comments" => [],
                "activities" => [
                    ["action" => "created", "description" => "Contact imported from CSV"],
                ],
            ],
            [
                "name" => "Charlie Davis",
                "company" => "Finance Hub",
                "phone" => "+15554445555",
                "email" => "charlie@financehub.com",
                "is_flagged" => true,
                "labels" => ["Finance", "Priority"],
                "comments" => [
                    "Urgent: Need call recording compliance features.",
                ],
                "activities" => [
                    ["action" => "created", "description" => "Contact created"],
                    ["action" => "called", "description" => "Support call - 15 min"],
                ],
            ],
        ];

        foreach ($contacts as $contactData) {
            $contact = DialerContact::create([
                "created_by" => $user ? $user->id : null,
                "name" => $contactData["name"],
                "company" => $contactData["company"],
                "phone" => $contactData["phone"],
                "phone_normalized" => preg_replace("/\D+/", "", $contactData["phone"]),
                "email" => $contactData["email"],
                "is_flagged" => $contactData["is_flagged"],
                "labels" => $contactData["labels"],
            ]);

            foreach ($contactData["comments"] as $comment) {
                DialerContactComment::create([
                    "dialer_contact_id" => $contact->id,
                    "user_id" => $user ? $user->id : null,
                    "body" => $comment,
                ]);
            }

            foreach ($contactData["activities"] as $activity) {
                DialerContactActivity::create([
                    "dialer_contact_id" => $contact->id,
                    "user_id" => $user ? $user->id : null,
                    "action" => $activity["action"],
                    "description" => $activity["description"],
                ]);
            }
        }
    }

    private function seedCallLogs(): void
    {
        $user = User::where("email", "admin@webphone.local")->first();
        $userId = $user ? $user->id : null;

        $callLogs = [
            [
                "destination" => "+15551234567",
                "caller_id" => "1001",
                "status" => "completed",
                "duration_seconds" => 320,
                "sip_status" => "200",
                "hangup_cause" => "NORMAL_CLEARING",
                "recording_path" => "recordings/test-call-1.wav",
            ],
            [
                "destination" => "+15559876543",
                "caller_id" => "1001",
                "status" => "completed",
                "duration_seconds" => 180,
                "sip_status" => "200",
                "hangup_cause" => "NORMAL_CLEARING",
                "recording_path" => "recordings/test-call-2.wav",
            ],
            [
                "destination" => "+15551112222",
                "caller_id" => "1002",
                "status" => "missed",
                "duration_seconds" => 0,
                "sip_status" => "487",
                "hangup_cause" => "ORIGINATOR_CANCEL",
                "recording_path" => null,
            ],
            [
                "destination" => "+15552223333",
                "caller_id" => "1001",
                "status" => "completed",
                "duration_seconds" => 480,
                "sip_status" => "200",
                "hangup_cause" => "NORMAL_CLEARING",
                "recording_path" => "recordings/test-call-3.wav",
            ],
            [
                "destination" => "+15554445555",
                "caller_id" => "1003",
                "status" => "failed",
                "duration_seconds" => 0,
                "sip_status" => "486",
                "hangup_cause" => "USER_BUSY",
                "recording_path" => null,
            ],
            [
                "destination" => "+15551234567",
                "caller_id" => "1002",
                "status" => "completed",
                "duration_seconds" => 95,
                "sip_status" => "200",
                "hangup_cause" => "NORMAL_CLEARING",
                "recording_path" => "recordings/test-call-4.wav",
            ],
        ];

        foreach ($callLogs as $index => $log) {
            CallLog::create(array_merge($log, [
                "user_id" => $userId,
                "call_uuid" => Str::uuid()->toString(),
                "connected_at" => now()->subDays($index)->subMinutes(rand(0, 1440)),
                "ended_at" => now()->subDays($index)->subMinutes(rand(0, 1440))->addSeconds($log["duration_seconds"]),
            ]));
        }
    }
}
