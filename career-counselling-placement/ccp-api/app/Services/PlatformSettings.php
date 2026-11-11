<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PlatformSettings
{
    public const DEFAULTS = [
        'academic_session' => '',
        'default_capacity' => 20,
        'contact_email' => '',
        'contact_phone' => '',
        'contact_address' => '',
        'privacy_text' => 'Account and application data are used to operate career counselling and placement. Contact the platform administrator to request corrections or deletion.',
        'terms_text' => 'Use accurate information, respect other users, and submit only documents you own. Opportunity availability and hiring decisions are managed by the relevant organizations.',
        'site_name' => 'Career Counselling & Placement',
        'university_name' => 'Khwaja Yunus Ali University',
        'brand_tagline' => 'KYAU · Career Counselling & Placement',
        'brand_logo_path' => '',
        'nav_journey_label' => 'How it works',
        'nav_advisors_label' => 'Career advisors',
        'nav_opportunities_label' => 'Opportunities',
        'nav_sign_in_label' => 'Sign in',
        'nav_create_account_label' => 'Create account',
        'nav_back_home_label' => 'Back to home',
        'theme_primary_color' => '#08766F',
        'theme_primary_dark_color' => '#06645E',
        'theme_navy_color' => '#10254F',
        'hero_enabled' => '1',
        'hero_kicker' => 'YOUR NEXT CHAPTER STARTS HERE',
        'hero_title_first' => 'Build your skills.',
        'hero_title_second' => 'Find your direction.',
        'hero_description' => 'Connect with a career advisor, prepare your CV and explore opportunities—all in one place.',
        'hero_primary_cta' => 'Start your career journey',
        'hero_secondary_cta' => 'Go to your workspace',
        'hero_note' => 'Guidance, preparation and real opportunities in one journey',
        'hero_card_title' => 'Your career journey',
        'hero_card_subtitle' => 'A clear path, one step at a time',
        'hero_visual_footer' => 'From first profile to next opportunity',
        'hero_float_title' => 'CV feedback',
        'hero_float_subtitle' => 'Prepare with confidence',
        'stats_enabled' => '1',
        'stats_kicker' => 'A COMMUNITY IN MOTION',
        'stats_text' => 'Building connections that move careers forward.',
        'section_order' => 'journey,advisors,opportunities,faq,contact',
        'journey_enabled' => '1',
        'journey_home_enabled' => '0',
        'journey_kicker' => 'YOUR JOURNEY',
        'journey_title' => 'A clear path from preparation to placement',
        'journey_description' => 'Every next step is easier when guidance, preparation and opportunities work together.',
        'journey_step_1_title' => 'Create your profile',
        'journey_step_1_body' => 'Tell us about your education, experience and goals.',
        'journey_step_2_title' => 'Meet your advisor',
        'journey_step_2_body' => 'Request an advisor and plan your next steps.',
        'journey_step_3_title' => 'Prepare and apply',
        'journey_step_3_body' => 'Get CV feedback and apply to approved opportunities.',
        'journey_step_4_title' => 'Take the next step',
        'journey_step_4_body' => 'Track interviews, offers and your placement.',
        'advisors_enabled' => '1',
        'advisors_home_enabled' => '1',
        'advisors_kicker' => 'GUIDANCE THAT FITS',
        'advisors_title' => 'Meet your career advisors',
        'advisors_description' => 'Get support as you shape your goals and plan the steps ahead.',
        'advisors_empty' => 'Advisor profiles will appear when accounts are approved.',
        'opportunities_enabled' => '1',
        'opportunities_home_enabled' => '0',
        'opportunities_kicker' => 'EXPLORE WHAT\'S NEXT',
        'opportunities_title' => 'Open opportunities',
        'opportunities_description' => 'Discover current openings and find a next step that matches your goals.',
        'opportunities_empty' => 'No open opportunities currently.',
        'opportunity_sign_in_label' => 'Sign in to explore',
        'faq_enabled' => '1',
        'faq_home_enabled' => '0',
        'faq_kicker' => 'GOOD TO KNOW',
        'faq_title' => 'Questions, answered',
        'faq_description' => 'A little more clarity before you get started.',
        'faq_1_question' => 'Who can create an account?',
        'faq_1_answer' => 'Students, Career Advisors, Recruiters and Admins can register. Staff accounts require approval from an active administrator.',
        'faq_2_question' => 'Do I need a university email?',
        'faq_2_answer' => 'No. Use your own valid email address. Email verification is currently deferred.',
        'faq_3_question' => 'When is my application sent to a company?',
        'faq_3_answer' => 'Only after an administrator approves and forwards it. You can track its status in your workspace.',
        'faq_4_question' => '',
        'faq_4_answer' => '',
        'contact_enabled' => '1',
        'contact_home_enabled' => '0',
        'contact_kicker' => 'HERE TO HELP',
        'contact_title' => 'Your next step starts with a conversation.',
        'contact_description' => 'Have a question? Contact your department office or platform administrator for support.',
        'contact_cta' => 'Start your journey',
        'footer_enabled' => '1',
        'footer_explore_label' => 'Explore',
        'footer_information_label' => 'Information',
        'footer_privacy_label' => 'Privacy',
        'footer_terms_label' => 'Terms',
        'footer_sign_in_label' => 'Sign in',
        'footer_copyright' => 'All rights reserved.',
        'privacy_title' => 'Privacy notice',
        'terms_title' => 'Terms of use',
        'legal_back_label' => 'Back to home',
        'announcement_enabled' => '0',
        'announcement_text' => '',
    ];

    private const PUBLIC_TOGGLES = [
        'hero_enabled',
        'stats_enabled',
        'journey_enabled',
        'advisors_enabled',
        'opportunities_enabled',
        'faq_enabled',
        'contact_enabled',
        'footer_enabled',
        'journey_home_enabled',
        'advisors_home_enabled',
        'opportunities_home_enabled',
        'faq_home_enabled',
        'contact_home_enabled',
        'announcement_enabled',
    ];

    public static function all(): array
    {
        return array_replace(self::DEFAULTS, DB::table('settings')->pluck('value', 'key')->all());
    }

    public static function get(string $key, $default = null)
    {
        return DB::table('settings')->where('key', $key)->value('value') ?? $default;
    }

    public static function public(): array
    {
        $settings = self::all();
        $settings['section_order'] = array_values(array_filter(explode(',', $settings['section_order'])));
        foreach (self::PUBLIC_TOGGLES as $key) {
            $settings[$key] = $settings[$key] === '1' || $settings[$key] === 1;
        }
        $settings['journey_steps'] = [];
        for ($i = 1; $i <= 4; $i++) {
            $settings['journey_steps'][] = [
                'title' => $settings["journey_step_{$i}_title"],
                'body' => $settings["journey_step_{$i}_body"],
            ];
        }
        $settings['faqs'] = [];
        for ($i = 1; $i <= 4; $i++) {
            $question = trim($settings["faq_{$i}_question"]);
            if ($question !== '') {
                $settings['faqs'][] = [
                    'question' => $question,
                    'answer' => $settings["faq_{$i}_answer"],
                ];
            }
        }
        unset($settings['default_capacity']);
        unset($settings['brand_logo_path']);

        return $settings;
    }
}
