<?php

namespace App\Console\Commands;

use App\Models\LegalCase;
use App\Models\Document;
use App\Models\Summary;
use Illuminate\Console\Command;

class GenerateTestDocuments extends Command
{
    protected $signature = 'test:generate-docs {--case_id= : ID of case to attach documents to}';
    protected $description = 'Generate dummy PDFs for all legal stages';

    public function handle(): int
    {
        $caseId = $this->option('case_id');
        
        if (!$caseId) {
            // Create a fresh test case
            $case = LegalCase::create([
                'user_id' => 1,
                'reference' => LegalCase::generateReference(),
                'debtor_name' => 'Test Debtor Ltd',
                'debtor_contact' => 'P.O. Box 12345-00100, Nairobi',
                'notes' => 'Auto-generated test case for document testing',
                'status' => 'active',
            ]);
            $caseId = $case->id;
            $this->info("Created test case: {$case->reference}");
        }

        $stages = $this->getDocumentStages();

        foreach ($stages as $index => $stage) {
            $this->info("Generating: {$stage['label']}...");
            
            $content = $this->buildPdfContent($stage);
            $filename = str_replace(' ', '_', strtolower($stage['label'])) . '.pdf';
            $path = storage_path("app/test-documents/{$filename}");
            
            // Ensure directory exists
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            
            // Generate PDF with DomPDF or fall back to plain text
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($content);
                $pdf->save($path);
            } else {
                // Fallback: use mPDF or store as text wrapped in HTML
                file_put_contents($path, $content);
                $this->warn("  ↳ DomPDF not installed — saved as HTML. Run: composer require barryvdh/laravel-dompdf");
                $this->warn("  ↳ File saved to: {$path} (rename to .html to view)");
            }

            // Register in database
            Document::create([
                'user_id'           => 1,
                'case_id'           => $caseId,
                'original_filename' => $filename,
                'storage_path'      => "test-documents/{$filename}",
                'file_size_bytes'   => filesize($path),
                'mime_type'         => 'application/pdf',
                'document_type'     => $stage['key'],
            ]);

            // Give a small delay so created_at timestamps differ
            usleep(100000);
        }

        $this->info('Done! All 14 test documents generated.');
        $this->info("View case at: " . route('cases.show', $caseId));
        
        return self::SUCCESS;
    }

    private function getDocumentStages(): array
    {
        return [
            ['key' => 'letter_of_instruction', 'label' => 'Letter of Instruction', 'date' => '2026-01-10'],
            ['key' => 'demand_letter', 'label' => 'Demand Letter', 'date' => '2026-01-24'],
            ['key' => 'instruction_to_file', 'label' => 'Instruction to File', 'date' => '2026-02-14'],
            ['key' => 'plaint', 'label' => 'Plaint', 'date' => '2026-02-28'],
            ['key' => 'affidavit_of_service', 'label' => 'Affidavit of Service', 'date' => '2026-03-10'],
            ['key' => 'defence', 'label' => 'Defence', 'date' => '2026-03-25'],
            ['key' => 'request_for_judgment', 'label' => 'Request for Judgment', 'date' => '2026-04-15'],
            ['key' => 'default_judgment', 'label' => 'Default Judgment', 'date' => '2026-04-30'],
            ['key' => 'hearing_judgment', 'label' => 'Hearing & Judgment', 'date' => '2026-05-10'],
            ['key' => 'decree', 'label' => 'Decree', 'date' => '2026-05-20'],
            ['key' => 'warrants', 'label' => 'Warrants', 'date' => '2026-06-01'],
            ['key' => 'proclamation', 'label' => 'Proclamation', 'date' => '2026-06-10'],
            ['key' => 'evidence_of_payment', 'label' => 'Evidence of Payment', 'date' => '2026-06-20'],
            ['key' => 'memorandum_of_appeal', 'label' => 'Memorandum of Appeal', 'date' => '2026-07-05'],
        ];
    }

    private function buildPdfContent(array $stage): string
    {
        $date = $stage['date'];
        
        // Document templates sourced from real Kenyan legal precedents
        $templates = [
            'Letter of Instruction' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p><strong>NYABOCHWA & OYORI LAW ADVOCATES</strong><br>
                Advocates & Commissioners for Oaths<br>
                P.O. Box 12345-00100, Nairobi<br>
                Tel: +254 700 000 000</p>
                <p>{$date}</p>
                <p><strong>LETTER OF INSTRUCTION</strong></p>
                <p>To: The Auctioneer<br>
                P.O. Box 67890-00100, Nairobi</p>
                <p>Dear Sir,</p>
                <p><strong>RE: INSTRUCTION TO RECOVER DEBT FROM TEST DEBTOR LTD</strong></p>
                <p>We act for our client, ABC Creditors Ltd, who obtained a decree in Civil Suit No. 45 of 2025 against Test Debtor Ltd for Kshs 850,000/= plus costs and interest.</p>
                <p>We hereby instruct you to proceed with execution by way of attachment and sale of the judgment debtor's movable property in execution of the decree dated 20th December 2025.</p>
                <p>Enclosed please find:</p>
                <ol>
                    <li>Copy of the Decree</li>
                    <li>Warrants of Attachment and Sale</li>
                    <li>Certificate of Costs</li>
                </ol>
                <p>Kindly proceed accordingly and keep us updated on the progress of execution.</p>
                <p>Yours faithfully,<br><br><br>NYABOCHWA & OYORI LAW ADVOCATES</p>
                </body></html>
                HTML,

            'Demand Letter' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p><strong>MUTHII W.M & ASSOCIATES</strong><br>
                Advocates, Commissioners for Oaths & Notaries Public<br>
                P.O. Box 54321-00100, Nairobi<br>
                Tel: +254 711 000 000</p>
                <p>{$date}</p>
                <p>The Managing Director<br>
                Test Debtor Ltd<br>
                P.O. Box 12345-00100, Nairobi</p>
                <p>Dear Sir,</p>
                <p><strong>RE: DEMAND FOR PAYMENT OF KENYA SHILLINGS 850,000/=</strong></p>
                <p>We act for ABC Creditors Ltd ("our client").</p>
                <p>Our client entered into a supply agreement with you on 15th July 2025 for the supply of construction materials valued at Kshs 850,000/=. Our client duly supplied the said materials and delivered them to your site on 30th July 2025.</p>
                <p>It was a term of the agreement that payment would be made within 30 days of delivery. Despite the expiry of the payment period and despite numerous reminders, you have failed, refused, and/or neglected to make payment.</p>
                <p><strong>TAKE NOTICE</strong> that unless payment of the full sum of Kshs 850,000/= is made within fourteen (14) days from the date hereof, we have firm instructions to file suit against you without further reference. You will also be liable for interest and legal costs.</p>
                <p>This letter is issued pursuant to Order 3 Rule 2 of the Civil Procedure Rules, 2010.</p>
                <p>Yours faithfully,<br><br><br>MUTHII W.M & ASSOCIATES</p>
                </body></html>
                HTML,

            'Instruction to File' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p><strong>INTERNAL MEMORANDUM</strong></p>
                <p>Date: {$date}<br>
                From: Managing Partner<br>
                To: Litigation Department</p>
                <p><strong>RE: INSTRUCTION TO FILE SUIT – ABC CREDITORS LTD vs. TEST DEBTOR LTD</strong></p>
                <p>Our demand letter dated 24th January 2026 to Test Debtor Ltd has gone unanswered. The 14-day notice period has lapsed.</p>
                <p>You are hereby instructed to:</p>
                <ol>
                    <li>Draft and file a Plaint in the Chief Magistrate's Court at Nairobi</li>
                    <li>Prepare the Verifying Affidavit and List of Witnesses</li>
                    <li>Prepare the List of Documents</li>
                    <li>File the suit within 7 days</li>
                </ol>
                <p>The claim is for Kshs 850,000/= plus interest and costs.</p>
                <p>All relevant documents are attached.</p>
                <p>Signed,<br><br>Managing Partner</p>
                </body></html>
                HTML,

            'Plaint' => <<<HTML
                <html><body style="font-family: serif; padding: 40px; font-size: 13px;">
                <p style="text-align:center;"><strong>REPUBLIC OF KENYA</strong></p>
                <p style="text-align:center;"><strong>IN THE CHIEF MAGISTRATE'S COURT AT NAIROBI</strong></p>
                <p style="text-align:center;"><strong>CIVIL SUIT NO. 45 OF 2026</strong></p>
                <p>ABC CREDITORS LTD……………………………………..………PLAINTIFF<br>
                -VERSUS-<br>
                TEST DEBTOR LTD…………………………………….……..……DEFENDANT</p>
                <p style="text-align:center;"><strong>PLAINT</strong></p>
                <p>1. The Plaintiff is a limited liability company duly incorporated under the Companies Act, 2015, carrying on business in Nairobi within the Republic of Kenya.</p>
                <p>2. The Defendant is a limited liability company carrying on business in Nairobi within the Republic of Kenya.</p>
                <p>3. On or about 15th July 2025, the Plaintiff entered into a written agreement with the Defendant for the supply of construction materials at a price of Kenya Shillings Eight Hundred and Fifty Thousand (Kshs 850,000/=).</p>
                <p>4. The Plaintiff duly supplied and delivered the said materials on 30th July 2025.</p>
                <p>5. It was a term of the agreement that payment would be made within 30 days of delivery.</p>
                <p>6. The Defendant, in breach of the agreement, has failed, ignored, and/or refused to pay the sum of Kshs 850,000/= despite repeated requests.</p>
                <p><strong>PARTICULARS OF BREACH:</strong></p>
                <p>(a) Failing to effect payment as agreed.<br>
                (b) Failing to honour its contractual obligations.</p>
                <p><strong>REASONS WHEREFORE</strong> the Plaintiff prays for judgment against the Defendant for:</p>
                <p>(a) Kshs 850,000/=<br>
                (b) Interest at court rates from the date of filing suit until payment in full<br>
                (c) Costs of this suit<br>
                (d) Any other relief this Honourable Court deems fit to grant.</p>
                <p>DATED at Nairobi this {$date}.</p>
                <p>_________________________<br>
                <strong>NYABOCHWA & OYORI LAW ADVOCATES</strong><br>
                Advocates for the Plaintiff</p>
                </body></html>
                HTML,

            'Affidavit of Service' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p style="text-align:center;"><strong>REPUBLIC OF KENYA</strong></p>
                <p style="text-align:center;"><strong>IN THE CHIEF MAGISTRATE'S COURT AT NAIROBI</strong></p>
                <p style="text-align:center;"><strong>CIVIL SUIT NO. 45 OF 2026</strong></p>
                <p>ABC CREDITORS LTD…………………………PLAINTIFF<br>
                -VERSUS-<br>
                TEST DEBTOR LTD…………………………DEFENDANT</p>
                <p style="text-align:center;"><strong>AFFIDAVIT OF SERVICE</strong></p>
                <p style="text-align:center;">(Pursuant to Order 5 Rule 15 of the Civil Procedure Rules)</p>
                <p>I, <strong>PETER OTIENO</strong> of P.O. Box 45678-00100, Nairobi, a duly authorised process server of the High Court of Kenya, do make oath and state as follows:</p>
                <p>1. THAT I am a process server duly authorised to serve civil processes.</p>
                <p>2. THAT on the 5th day of March 2026, I received copies of the Summons to Enter Appearance, Plaint, Verifying Affidavit, List of Witnesses, and List of Documents from Nyabochwa & Oyori Law Advocates with instructions to serve the same upon the Defendant.</p>
                <p>3. THAT on the {$date}, at approximately 10:30 am, I proceeded to the Defendant's offices located at Moi Avenue, Ngano House, 4th Floor, Nairobi.</p>
                <p>4. THAT upon arrival, I met the Defendant's Managing Director, who identified himself to me, and I tendered copies of the documents to him.</p>
                <p>5. THAT the said Managing Director received the documents and signed on the back of the principal copy in acknowledgment of receipt.</p>
                <p>6. THAT what is deponed herein is true to the best of my knowledge and belief.</p>
                <p>SWORN by the said PETER OTIENO<br>
                at Nairobi this {$date}</p>
                <p>_________________________<br>
                <strong>PETER OTIENO</strong></p>
                <p>Before me:<br>
                _________________________<br>
                <strong>COMMISSIONER FOR OATHS</strong></p>
                </body></html>
                HTML,

            'Defence' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p style="text-align:center;"><strong>REPUBLIC OF KENYA</strong></p>
                <p style="text-align:center;"><strong>IN THE CHIEF MAGISTRATE'S COURT AT NAIROBI</strong></p>
                <p style="text-align:center;"><strong>CIVIL SUIT NO. 45 OF 2026</strong></p>
                <p>ABC CREDITORS LTD…………………………PLAINTIFF<br>
                -VERSUS-<br>
                TEST DEBTOR LTD…………………………DEFENDANT</p>
                <p style="text-align:center;"><strong>STATEMENT OF DEFENCE</strong></p>
                <p>1. Save what is hereinafter expressly admitted, the Defendant denies each and every allegation in the Plaint as though the same were herein set out verbatim and traversed seriatim.</p>
                <p>2. The Defendant admits the contents of paragraphs 1, 2, and 3 of the Plaint in so far as the same are merely descriptive of the parties and the existence of a supply agreement.</p>
                <p>3. The Defendant denies paragraph 4 of the Plaint and avers that the materials supplied by the Plaintiff were substandard and not of the quality agreed upon in the contract.</p>
                <p>4. In response to paragraphs 5 and 6, the Defendant avers that it was an implied term of the agreement that payment was conditional upon the supply of materials meeting the agreed specifications, which condition was never satisfied.</p>
                <p>5. The Defendant avers that it communicated its concerns regarding the quality of materials to the Plaintiff on 5th August 2025, but the Plaintiff failed to remedy the defects.</p>
                <p>6. The Defendant denies the Plaintiff's claim for Kshs 850,000/= and puts the Plaintiff to strict proof thereof.</p>
                <p>DATED at Nairobi this {$date}.</p>
                <p>_________________________<br>
                <strong>ODABA, TRACY & KEREU ADVOCATES</strong><br>
                Advocates for the Defendant</p>
                </body></html>
                HTML,

            'Request for Judgment' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p style="text-align:center;"><strong>REPUBLIC OF KENYA</strong></p>
                <p style="text-align:center;"><strong>IN THE CHIEF MAGISTRATE'S COURT AT NAIROBI</strong></p>
                <p style="text-align:center;"><strong>CIVIL SUIT NO. 45 OF 2026</strong></p>
                <p>ABC CREDITORS LTD…………………………PLAINTIFF<br>
                -VERSUS-<br>
                TEST DEBTOR LTD…………………………DEFENDANT</p>
                <p style="text-align:center;"><strong>REQUEST FOR JUDGMENT</strong></p>
                <p style="text-align:center;">(Pursuant to Order 10 Rule 6 of the Civil Procedure Rules)</p>
                <p>The Plaintiff hereby requests that judgment be entered against the Defendant for the sum of Kshs 850,000/= as claimed in the Plaint.</p>
                <p>The Defendant was served with Summons to Enter Appearance on 10th March 2026 as evidenced by the Affidavit of Service filed on {$date}.</p>
                <p>The Defendant has failed to enter appearance and/or file a defence within the prescribed period of fifteen (15) days from the date of service.</p>
                <p>The Plaintiff therefore requests that interlocutory judgment be entered in default of appearance and defence.</p>
                <p>DATED at Nairobi this {$date}.</p>
                <p>_________________________<br>
                <strong>NYABOCHWA & OYORI LAW ADVOCATES</strong><br>
                Advocates for the Plaintiff</p>
                </body></html>
                HTML,

            'Default Judgment' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p style="text-align:center;"><strong>REPUBLIC OF KENYA</strong></p>
                <p style="text-align:center;"><strong>IN THE CHIEF MAGISTRATE'S COURT AT NAIROBI</strong></p>
                <p style="text-align:center;"><strong>CIVIL SUIT NO. 45 OF 2026</strong></p>
                <p>ABC CREDITORS LTD…………………………PLAINTIFF<br>
                -VERSUS-<br>
                TEST DEBTOR LTD…………………………DEFENDANT</p>
                <p style="text-align:center;"><strong>JUDGMENT IN DEFAULT</strong></p>
                <p style="text-align:center;">(Order 10 Rule 6, Civil Procedure Rules, 2010)</p>
                <p>UPON the Plaintiff filing a Request for Judgment on 15th April 2026;</p>
                <p>AND UPON perusing the Affidavit of Service filed herein confirming service of Summons upon the Defendant on 10th March 2026;</p>
                <p>AND UPON noting that the Defendant has failed to enter appearance and/or file a defence within the prescribed time;</p>
                <p><strong>IT IS HEREBY ORDERED:</strong></p>
                <p>1. THAT interlocutory judgment be and is hereby entered against the Defendant for the sum of Kshs 850,000/=.</p>
                <p>2. THAT the matter shall proceed for formal proof for assessment of damages.</p>
                <p>3. THAT costs shall be in the cause.</p>
                <p>GIVEN under my hand and the seal of this court this {$date}.</p>
                <p>_________________________<br>
                <strong>HON. MAGISTRATE</strong><br>
                Chief Magistrate's Court, Nairobi</p>
                </body></html>
                HTML,

            'Hearing & Judgment' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p style="text-align:center;"><strong>REPUBLIC OF KENYA</strong></p>
                <p style="text-align:center;"><strong>IN THE CHIEF MAGISTRATE'S COURT AT NAIROBI</strong></p>
                <p style="text-align:center;"><strong>CIVIL SUIT NO. 45 OF 2026</strong></p>
                <p>ABC CREDITORS LTD…………………………PLAINTIFF<br>
                -VERSUS-<br>
                TEST DEBTOR LTD…………………………DEFENDANT</p>
                <p style="text-align:center;"><strong>JUDGMENT</strong></p>
                <p>This matter came up for formal proof on {$date} before the Honourable Magistrate.</p>
                <p>The Plaintiff called one witness, John Mwangi, the Managing Director of the Plaintiff company. He testified that the Plaintiff supplied construction materials to the Defendant on 30th July 2025 valued at Kshs 850,000/=. The Defendant failed to pay despite repeated demands.</p>
                <p>The witness produced the following exhibits:</p>
                <ol>
                    <li>Supply Agreement dated 15th July 2025 — Exhibit P1</li>
                    <li>Delivery Note dated 30th July 2025 — Exhibit P2</li>
                    <li>Demand Letter dated 24th January 2026 — Exhibit P3</li>
                </ol>
                <p>Having considered the evidence on record and the submissions by counsel for the Plaintiff, I am satisfied that the Plaintiff has proved its case on a balance of probabilities.</p>
                <p><strong>IT IS HEREBY ORDERED:</strong></p>
                <p>1. Judgment is entered in favour of the Plaintiff against the Defendant for Kshs 850,000/=.</p>
                <p>2. Interest at court rates from the date of filing suit until payment in full.</p>
                <p>3. Costs of the suit to the Plaintiff.</p>
                <p>DATED and DELIVERED at Nairobi this {$date}.</p>
                <p>_________________________<br>
                <strong>HON. E.K. USUI (CHIEF MAGISTRATE)</strong></p>
                </body></html>
                HTML,

            'Decree' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p style="text-align:center;"><strong>REPUBLIC OF KENYA</strong></p>
                <p style="text-align:center;"><strong>IN THE CHIEF MAGISTRATE'S COURT AT NAIROBI</strong></p>
                <p style="text-align:center;"><strong>CIVIL SUIT NO. 45 OF 2026</strong></p>
                <p>ABC CREDITORS LTD…………………………PLAINTIFF<br>
                -VERSUS-<br>
                TEST DEBTOR LTD…………………………DEFENDANT</p>
                <p style="text-align:center;"><strong>DECREE</strong></p>
                <p><strong>CLAIM FOR:</strong> (a) Debt recovery in the sum of Kshs 850,000/= (b) Interest at court rates (c) Costs of the suit.</p>
                <p>THIS MATTER coming up before the Honourable E.K. Usui, Chief Magistrate, for judgment on 10th May 2026 in the presence of Mr. Oyori for the Plaintiff and the Defendant having failed to appear.</p>
                <p><strong>IT IS HEREBY ORDERED THAT:</strong></p>
                <p>Judgment be and is hereby entered for the Plaintiff against the Defendant for:</p>
                <table>
                    <tr><td>i. Principal sum</td><td>Kshs 850,000.00</td></tr>
                    <tr><td>ii. Interest at court rates (12% p.a.)</td><td>Kshs 102,000.00</td></tr>
                    <tr><td>iii. Costs of the suit (taxed)</td><td>Kshs 150,000.00</td></tr>
                    <tr><td><strong>TOTAL</strong></td><td><strong>Kshs 1,102,000.00</strong></td></tr>
                </table>
                <p>THAT the Defendant do pay to the Plaintiff the sum of Kshs 1,102,000/=.</p>
                <p>GIVEN under my hand and the Seal of this court this {$date}.</p>
                <p>_________________________<br>
                <strong>DEPUTY REGISTRAR</strong><br>
                Chief Magistrate's Court, Nairobi</p>
                </body></html>
                HTML,

            'Warrants' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p style="text-align:center;"><strong>REPUBLIC OF KENYA</strong></p>
                <p style="text-align:center;"><strong>IN THE CHIEF MAGISTRATE'S COURT AT NAIROBI</strong></p>
                <p style="text-align:center;"><strong>CIVIL SUIT NO. 45 OF 2026</strong></p>
                <p style="text-align:center;"><strong>WARRANTS OF ATTACHMENT AND SALE</strong></p>
                <p style="text-align:center;">(Pursuant to Order 22 Rule 11 of the Civil Procedure Rules)</p>
                <p>TO: Icon Auctioneers<br>
                P.O. Box 56789-00100, Nairobi</p>
                <p>WHEREAS ABC Creditors Ltd (hereinafter "the Decree-Holder") obtained a Decree against Test Debtor Ltd (hereinafter "the Judgment-Debtor") in the above suit on 20th May 2026 for the sum of Kshs 1,102,000/=.</p>
                <p>AND WHEREAS the Judgment-Debtor has failed to satisfy the said Decree.</p>
                <p><strong>YOU ARE HEREBY COMMANDED</strong> to attach the movable property of the Judgment-Debtor and sell the same by public auction to realise the decretal sum of Kshs 1,102,000/= together with your fees and charges as per the Auctioneers Rules.</p>
                <p>DATED at Nairobi this {$date}.</p>
                <p>_________________________<br>
                <strong>DEPUTY REGISTRAR</strong><br>
                Chief Magistrate's Court, Nairobi</p>
                </body></html>
                HTML,

            'Proclamation' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p style="text-align:center;"><strong>ICON AUCTIONEERS</strong></p>
                <p style="text-align:center;">P.O. Box 56789-00100, Nairobi<br>
                Tel: +254 722 000 000</p>
                <p>{$date}</p>
                <p style="text-align:center;"><strong>PROCLAMATION NOTICE</strong></p>
                <p style="text-align:center;">(Pursuant to Rule 12 of the Auctioneers Rules, 1997)</p>
                <p><strong>TO: The Managing Director, Test Debtor Ltd</strong><br>
                Moi Avenue, Ngano House, 4th Floor, Nairobi</p>
                <p><strong>RE: PROCLAMATION OF ATTACHMENT IN EXECUTION OF DECREE IN CMCC NO. 45 OF 2026</strong></p>
                <p>WHEREAS we have received Warrants of Attachment and Sale dated 1st June 2026 against you for the sum of Kshs 1,102,000/=.</p>
                <p>WE HEREBY GIVE YOU NOTICE that we have this day attached the following movable properties found at your premises:</p>
                <table border="1" cellpadding="5">
                    <tr><th>Item</th><th>Description</th><th>Estimated Value (Kshs)</th></tr>
                    <tr><td>1.</td><td>Office desks and chairs (8 sets)</td><td>160,000</td></tr>
                    <tr><td>2.</td><td>Computers (8 units)</td><td>320,000</td></tr>
                    <tr><td>3.</td><td>Printers (3 units)</td><td>90,000</td></tr>
                    <tr><td>4.</td><td>Filing cabinets (5 units)</td><td>75,000</td></tr>
                    <tr><td>5.</td><td>Office safe</td><td>50,000</td></tr>
                    <tr><td colspan="2"><strong>TOTAL</strong></td><td><strong>695,000</strong></td></tr>
                </table>
                <p>TAKE NOTICE that unless the decretal sum together with our charges is paid within seven (7) days from the date hereof, the proclaimed goods shall be sold by public auction.</p>
                <p>_________________________<br>
                <strong>ICON AUCTIONEERS</strong></p>
                </body></html>
                HTML,

            'Evidence of Payment' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p style="text-align:center;"><strong>RECEIPT</strong></p>
                <p><strong>Receipt No:</strong> RCPT-2026-0615</p>
                <p><strong>Date:</strong> {$date}</p>
                <p><strong>Received from:</strong> Test Debtor Ltd<br>
                <strong>P.O. Box:</strong> 12345-00100, Nairobi</p>
                <p><strong>The sum of:</strong> Kenya Shillings One Million One Hundred and Two Thousand Only (Kshs 1,102,000/=)</p>
                <p><strong>Being payment of:</strong> Full and final settlement of the decretal sum in CMCC No. 45 of 2026 — ABC Creditors Ltd vs. Test Debtor Ltd.</p>
                <p><strong>Payment Method:</strong> RTGS Transfer<br>
                <strong>Bank:</strong> Kenya Commercial Bank, Moi Avenue Branch<br>
                <strong>Account:</strong> 1234567890</p>
                <p><strong>Received by:</strong><br><br>
                _________________________<br>
                <strong>NYABOCHWA & OYORI LAW ADVOCATES</strong><br>
                Advocates for the Decree-Holder</p>
                <p style="text-align:center;"><strong>OFFICIAL RECEIPT — RETAIN FOR YOUR RECORDS</strong></p>
                </body></html>
                HTML,

            'Memorandum of Appeal' => <<<HTML
                <html><body style="font-family: serif; padding: 40px;">
                <p style="text-align:center;"><strong>REPUBLIC OF KENYA</strong></p>
                <p style="text-align:center;"><strong>IN THE HIGH COURT OF KENYA AT NAIROBI</strong></p>
                <p style="text-align:center;"><strong>CIVIL APPEAL NO. 78 OF 2026</strong></p>
                <p>TEST DEBTOR LTD…………………………APPELLANT<br>
                -VERSUS-<br>
                ABC CREDITORS LTD…………………………RESPONDENT</p>
                <p style="text-align:center;"><strong>MEMORANDUM OF APPEAL</strong></p>
                <p>(An appeal from the Judgment and Decree of the Honourable E.K. Usui, Chief Magistrate, delivered on 10th May 2026 in Chief Magistrate's Court Civil Suit No. 45 of 2026 at Nairobi)</p>
                <p>TEST DEBTOR LTD, hereinafter referred to as the Appellant, being dissatisfied and/or aggrieved with the whole JUDGMENT and DECREE of Honourable E.K. Usui delivered on 10th May 2026, now appeals to this Honourable Court setting forth the following grounds of appeal:</p>
                <p>a) THAT the Learned Trial Magistrate erred in both law and fact by entering judgment in default of appearance and defence without affording the Appellant an opportunity to be heard.</p>
                <p>b) THAT the Learned Trial Magistrate erred in law by failing to find that the service of summons was irregular and did not comply with Order 5 of the Civil Procedure Rules.</p>
                <p>c) THAT the Learned Trial Magistrate erred by failing to consider that the materials supplied by the Respondent were substandard and not of the quality agreed upon.</p>
                <p>d) THAT the Learned Trial Magistrate erred in awarding costs against the Appellant in the circumstances of the case.</p>
                <p><strong>REASONS WHEREFORE</strong> the Appellant prays that:</p>
                <p>(a) This appeal be allowed.<br>
                (b) The judgment and decree of the lower court be set aside.<br>
                (c) Costs of this appeal be awarded to the Appellant.</p>
                <p>DATED at Nairobi this {$date}.</p>
                <p>_________________________<br>
                <strong>ODABA, TRACY & KEREU ADVOCATES</strong><br>
                Advocates for the Appellant</p>
                </body></html>
                HTML,
        ];

        return $templates[$stage['label']] ?? '<html><body><p>Test document for ' . $stage['label'] . '</p></body></html>';
    }
}