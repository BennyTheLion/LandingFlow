<?php
namespace App\Ai;

/**
 * ChatAgent — conversational lead-generation assistant.
 * Answers questions, guides visitors to conversion, captures leads.
 */
class ChatAgent
{
    /**
     * Generate a contextual response based on user query and conversation state.
     */
    public function respond(string $message, array $conversationHistory = [], ?string $visitorName = null): array
    {
        $msg = trim(strtolower($message));
        $response = '';
        $action = null;
        $capturedEmail = null;

        // Detect email sharing
        if (filter_var($message, FILTER_VALIDATE_EMAIL)) {
            $capturedEmail = $message;
            $response = "Thanks! We'll send your free website audit to $message. What's your name?";
            $action = 'capture_lead';
        }
        // Greeting / intro
        elseif (preg_match('/^(hi|hello|hey|shalom|good\s*(morning|afternoon|evening))/i', $msg)) {
            $name = $visitorName ? " $visitorName" : '';
            $response = "Hello$name! I'm the LandingFlow assistant. I can help you with:\n• Free website audit\n• Website pricing\n• Hosting plans\n• Demo examples\n\nWhat interests you?";
        }
        // Pricing
        elseif (preg_match('/(price|pricing|cost|how much|packages|plans)/i', $msg)) {
            $response = "Our plans start at flexible pricing based on your needs:\n• Landing Page: Custom design\n• Business Site: Full features\n• E-commerce: Online store\n\nWant a free audit first to see what you need? Drop your email!";
            $action = 'show_pricing';
        }
        // Services
        elseif (preg_match('/(service|offer|what do you|what can you|help with)/i', $msg)) {
            $response = "We offer:\n🚀 Website Development\n📄 Landing Pages\n🖥️ Hosting & Domains\n📊 Website Monitoring\n🔍 Free SEO Audits\n\nWhich service interests you?";
        }
        // Audit
        elseif (preg_match('/(audit|check|scan|analyze|review|free)/i', $msg)) {
            $response = "Great choice! Our free website audit checks:\n• SEO score\n• Performance\n• Security headers\n• Accessibility\n• Legal compliance\n\nJust send your email and website URL to get started!";
            $action = 'prompt_audit';
        }
        // Demo
        elseif (preg_match('/(demo|example|sample|portfolio|show me|see)/i', $msg)) {
            $response = "Check out our demo sites at /demo! You can see different styles and request a custom demo. Want me to guide you there?";
            $action = 'show_demo';
        }
        // Default
        else {
            $response = "I'd love to help! Here's what I can do:\n• Explain our services\n• Show pricing\n• Run a free website audit\n• Show demos\n\nWhat are you looking for?";
        }

        return [
            'response'       => $response,
            'action'         => $action,
            'captured_email' => $capturedEmail,
        ];
    }

    /**
     * Check if a message contains an email address for lead capture.
     */
    public function extractEmail(string $message): ?string
    {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message, $m)) {
            return $m[0];
        }
        return null;
    }

    /**
     * Get the CTA for a given conversation context.
     */
    public function suggestCta(array $history): string
    {
        $lastAction = $history[count($history) - 1]['action'] ?? '';
        $ctas = [
            'prompt_audit'  => 'Send your email to get your free audit →',
            'show_pricing'  => 'Ready to start? Drop your email for a custom quote →',
            'show_demo'     => 'Want a personalized demo? Share your email →',
            'capture_lead'  => 'Great! We\'ll be in touch within 24 hours →',
            ''              => 'Ask me anything about our services →',
        ];
        return $ctas[$lastAction] ?? 'Ask me anything about our services →';
    }
}
