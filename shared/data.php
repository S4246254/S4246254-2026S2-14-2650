<?php
/**
 * Seed data for the in-memory store.
 *
 * This is the "database" for Assessment Task 2: hard-coded associative arrays
 * that bootstrap.php copies into the PHP session on first request. Every
 * create, update and delete then works on the session copy, so the site
 * behaves fully while it is being used and forgets everything when the
 * session ends. Assessment Task 3 replaces this file with MySQL.
 *
 * Money is stored as integer cents. Dates are ISO 8601 strings.
 */

declare(strict_types=1);

function gf_seed_data(): array
{
    return [
        'next_id' => [
            'posts' => 100, 'threads' => 100, 'blog' => 100, 'comments' => 100,
            'reviews' => 100, 'cart_items' => 100, 'orders' => 4870,
        ],

        /* ------------------------------------------------------------------
           Users. No passwords: the brief asks for username-only login.
           ---------------------------------------------------------------- */
        'users' => [
            'kaya' => [
                'name'   => 'Kaya Ellery',
                'avatar' => 'assets/img/avatar-kaya.svg',
                'joined' => '2024-03-11',
                'email'  => 'kaya.ellery@example.com',
                'bio'    => 'Paints infantry, argues about points costs, writes most of the terrain threads.',
            ],
            'toma' => [
                'name'   => 'Toma Brandt',
                'avatar' => 'assets/img/avatar-toma.svg',
                'joined' => '2023-09-02',
                'email'  => 'toma.brandt@example.com',
                'bio'    => 'Tournament player. Posts lists, then defends them at length.',
            ],
            'renn' => [
                'name'   => 'Renn Vasco',
                'avatar' => 'assets/img/avatar-renn.svg',
                'joined' => '2022-11-19',
                'email'  => 'renn.vasco@example.com',
                'bio'    => 'Store staff. Writes the painting tutorials on the blog.',
            ],
            'oksana' => [
                'name'   => 'Oksana Reyes',
                'avatar' => 'assets/img/avatar-oksana.svg',
                'joined' => '2025-01-26',
                'email'  => 'oksana.reyes@example.com',
                'bio'    => 'Reviews almost everything she buys, usually within a week.',
            ],
        ],

        /* ------------------------------------------------------------------
           Products
           ---------------------------------------------------------------- */
        'products' => [
            'sentinel' => [
                'name'        => 'Ironclad Sentinel',
                'code'        => 'GF-KIT-0142',
                'category'    => 'kits',
                'scale'       => '28mm',
                'colour'      => 'unpainted',
                'meta'        => ['28 mm scale', 'Plastic kit'],
                'summary'     => 'Multi-part plastic walker with three head options and two weapon arms. Push-fit torso, no glue required.',
                'description' => "A multi-part plastic walker designed as the anchor of an infantry line. The torso is push-fit and needs no glue, while the arms, head and banner are separate components so the model can be built in several configurations.\n\nSupplied unpainted and unassembled, with a 60 mm rubble base and a transfer sheet of company markings.",
                'price_cents' => 4200,
                'was_cents'   => 4800,
                'stock'       => 12,
                'max_qty'     => 12,
                'image'       => 'assets/img/kit-sentinel.svg',
                'alt'         => 'A heavily armoured bipedal walker miniature with a slab shield on its left arm and a long-barrelled cannon on its right.',
                'gallery'     => [
                    ['assets/img/photo-sentinel-sprue.svg', 'The unbuilt plastic sprue, showing leg, torso and weapon components still attached to the frame.'],
                    ['assets/img/photo-sentinel-scale.svg', 'The assembled walker beside three infantry miniatures for scale, standing roughly twice their height.'],
                    ['assets/img/photo-primer.svg', 'The same walker primed in flat grey, before any colour has been applied.'],
                ],
                'specs'       => [
                    ['Scale', '28 mm heroic'], ['Material', 'Polystyrene plastic'], ['Components', '31 parts across two sprues'],
                    ['Assembled height', '74 mm to the top of the visor'], ['Base supplied', '60 mm round, plain or pre-textured'],
                    ['Assembly', 'Push-fit torso and legs; arms and head require plastic cement'],
                ],
                'variant_label' => 'Weapon arm',
                'variants'    => [
                    'cannon' => ['label' => 'Siege cannon', 'extra_cents' => 0],
                    'flamer' => ['label' => 'Twin flamer', 'extra_cents' => 0],
                    'both'   => ['label' => 'Both arms included', 'extra_cents' => 600],
                ],
            ],
            'warden' => [
                'name'        => 'Bastion Warden',
                'code'        => 'GF-KIT-0157',
                'category'    => 'kits',
                'scale'       => '32mm',
                'colour'      => 'unpainted',
                'meta'        => ['32 mm scale', 'Resin kit'],
                'summary'     => 'A single-piece resin character with a separate maul and cloak. Sharp casting; wash before priming.',
                'description' => "A resin character model cast in three parts: body, maul and cloak. The detail is sharp enough to need a wash in warm soapy water before priming, or the release agent will lift your paint.\n\nSupplied with a 40 mm base.",
                'price_cents' => 2900,
                'was_cents'   => null,
                'stock'       => 3,
                'max_qty'     => 3,
                'image'       => 'assets/img/kit-warden.svg',
                'alt'         => 'A broad-shouldered infantry miniature in layered plate armour holding a two-handed maul across its body.',
                'gallery'     => [],
                'specs'       => [['Scale', '32 mm'], ['Material', 'Polyurethane resin'], ['Components', '3 parts'], ['Base supplied', '40 mm round']],
                'variant_label' => 'Cloak',
                'variants'    => [
                    'cloak'   => ['label' => 'With cloak', 'extra_cents' => 0],
                    'nocloak' => ['label' => 'Without cloak', 'extra_cents' => 0],
                ],
            ],
            'scarab' => [
                'name'        => 'Scarab Skirmishers',
                'code'        => 'GF-KIT-0099',
                'category'    => 'kits',
                'scale'       => '15mm',
                'colour'      => 'unpainted',
                'meta'        => ['15 mm scale', 'Plastic kit'],
                'summary'     => 'Six constructs across two sprues, with alternate leg positions so no two need to look alike.',
                'description' => "Six six-legged constructs on two identical sprues. Each body takes any of three leg sets, so a unit can be posed climbing, crouched or scuttling without any two looking the same.\n\nSupplied with three 40 mm scenic bases, two models to a base.",
                'price_cents' => 2400,
                'was_cents'   => null,
                'stock'       => 21,
                'max_qty'     => 10,
                'image'       => 'assets/img/kit-scarab.svg',
                'alt'         => 'Three small six-legged mechanical constructs mounted on a single scenic base.',
                'gallery'     => [],
                'specs'       => [['Scale', '15 mm'], ['Material', 'Polystyrene plastic'], ['Components', '48 parts across two sprues'], ['Base supplied', 'Three 40 mm scenic bases']],
                'variant_label' => 'Base finish',
                'variants'    => [
                    'plain'  => ['label' => 'Plain bases', 'extra_cents' => 0],
                    'rubble' => ['label' => 'Pre-textured rubble bases', 'extra_cents' => 300],
                ],
            ],
            'oxide' => [
                'name'        => 'Oxide Wash',
                'code'        => 'GF-PNT-0031',
                'category'    => 'paints',
                'scale'       => '30ml',
                'colour'      => 'rust',
                'meta'        => ['30 ml pot', 'Acrylic shade'],
                'summary'     => 'A thin shade that settles into recessed detail. Made for bare metal and weathered plate.',
                'description' => "A thin acrylic shade that settles into recessed detail. Made for bare metal and weathered plate; thin it further with medium for glazing.\n\nDries matt. Best over a gloss coat, where it flows into panel lines without leaving a tidemark on flat surfaces.",
                'price_cents' => 899,
                'was_cents'   => 1150,
                'stock'       => 4,
                'max_qty'     => 4,
                'image'       => 'assets/img/paint-oxide.svg',
                'alt'         => 'A hexagonal paint pot with a rust-brown label, standing beside a swatch card showing the colour brushed over bare metal.',
                'gallery'     => [],
                'specs'       => [['Type', 'Acrylic shade'], ['Colour', 'Rust brown'], ['Finish', 'Matt'], ['Pot', '30 ml, screw top']],
                'variant_label' => 'Pot size',
                'variants'    => [
                    '30' => ['label' => '30 ml pot', 'extra_cents' => 0],
                    '60' => ['label' => '60 ml pot', 'extra_cents' => 650],
                ],
            ],
            'bone' => [
                'name'        => 'Bone Ivory',
                'code'        => 'GF-PNT-0012',
                'category'    => 'paints',
                'scale'       => '30ml',
                'colour'      => 'bone',
                'meta'        => ['30 ml pot', 'Acrylic base'],
                'summary'     => 'An opaque off-white that covers dark primer in two thin coats. Prone to chalkiness if applied thickly.',
                'description' => "An opaque off-white base colour. Covers black primer in two thin coats and takes a wash well.\n\nThin it: straight from the pot it dries chalky, especially over black. Two parts water to one part paint is a good starting ratio.",
                'price_cents' => 899,
                'was_cents'   => null,
                'stock'       => 33,
                'max_qty'     => 12,
                'image'       => 'assets/img/paint-bone.svg',
                'alt'         => 'A hexagonal paint pot with a pale bone-coloured label beside a swatch card showing the colour over dark primer.',
                'gallery'     => [],
                'specs'       => [['Type', 'Acrylic base'], ['Colour', 'Bone'], ['Finish', 'Matt'], ['Pot', '30 ml, screw top']],
                'variant_label' => 'Pot size',
                'variants'    => [
                    '30' => ['label' => '30 ml pot', 'extra_cents' => 0],
                    '60' => ['label' => '60 ml pot', 'extra_cents' => 650],
                ],
            ],
            'brushes' => [
                'name'        => 'Sable Brush Set',
                'code'        => 'GF-TOL-0008',
                'category'    => 'tools',
                'scale'       => '',
                'colour'      => '',
                'meta'        => ['Sizes 0, 1 and 2', 'Kolinsky sable'],
                'summary'     => 'Three natural-hair brushes that hold a point through a full session.',
                'description' => "Three Kolinsky sable brushes with wooden handles and seamless nickel ferrules. The belly holds enough paint for a full base coat, and the tip comes back to a point after every rinse.\n\nSized for faces, edge highlights and base coats. Clean with brush soap and store tip-up.",
                'price_cents' => 3450,
                'was_cents'   => null,
                'stock'       => 8,
                'max_qty'     => 8,
                'image'       => 'assets/img/brush-set.svg',
                'alt'         => 'Three wooden-handled brushes laid side by side in descending size, with their bristles pointed to fine tips.',
                'gallery'     => [],
                'specs'       => [['Hair', 'Kolinsky sable'], ['Sizes', '0, 1 and 2'], ['Ferrule', 'Seamless nickel'], ['Handle', 'Lacquered wood, short']],
                'variant_label' => 'Set contents',
                'variants'    => [
                    '012'   => ['label' => 'Sizes 0, 1 and 2', 'extra_cents' => 0],
                    '000-0' => ['label' => 'Sizes 000, 00 and 0', 'extra_cents' => 0],
                ],
            ],
            'ruins' => [
                'name'        => 'Ruined Chapel Set',
                'code'        => 'GF-TER-0021',
                'category'    => 'terrain',
                'scale'       => '28mm',
                'colour'      => 'steel',
                'meta'        => ['28 mm scale', 'Terrain'],
                'summary'     => 'Four wall sections and an archway in pre-textured resin. Assembles into one large ruin or two smaller pieces.',
                'description' => "Four wall sections and a broken archway cast in pre-textured resin. The sections key together at the corners so the set builds as one large ruin or two smaller pieces.\n\nSupplied unpainted. Takes a drybrush well straight out of the box.",
                'price_cents' => 7800,
                'was_cents'   => null,
                'stock'       => 0,
                'max_qty'     => 4,
                'image'       => 'assets/img/terrain-ruins.svg',
                'alt'         => 'A ruined stone wall section with a broken archway and rubble at its base, built as tabletop scenery.',
                'gallery'     => [],
                'specs'       => [['Scale', '28 mm'], ['Material', 'Polyurethane resin'], ['Components', '5 pieces'], ['Footprint', '30 cm by 20 cm assembled']],
                'variant_label' => 'Finish',
                'variants'    => [
                    'grey'   => ['label' => 'Unpainted grey resin', 'extra_cents' => 0],
                    'primed' => ['label' => 'Primed black', 'extra_cents' => 800],
                ],
            ],
            'rulebook' => [
                'name'        => 'Rules of Engagement, third edition',
                'code'        => 'GF-BOK-0003',
                'category'    => 'books',
                'scale'       => '',
                'colour'      => '',
                'meta'        => ['288 pages', 'Hardback'],
                'summary'     => 'The full ruleset with the 2026 errata folded in, plus six scenarios and a points appendix.',
                'description' => "The complete third-edition rules with the 2026 errata folded into the text rather than bolted on at the back. Six scenarios, a points appendix and a quick-reference sheet.\n\nHardback, sewn binding, ribbon marker.",
                'price_cents' => 6500,
                'was_cents'   => null,
                'stock'       => 15,
                'max_qty'     => 5,
                'image'       => 'assets/img/rulebook.svg',
                'alt'         => 'A closed hardback rulebook with an embossed anvil emblem on its cover.',
                'gallery'     => [],
                'specs'       => [['Pages', '288'], ['Binding', 'Hardback, sewn'], ['Edition', 'Third, 2026 errata included']],
                'variant_label' => 'Edition',
                'variants'    => [
                    'standard' => ['label' => 'Standard hardback', 'extra_cents' => 0],
                    'limited'  => ['label' => 'Limited edition slipcase', 'extra_cents' => 2500],
                ],
            ],
        ],

        /* ------------------------------------------------------------------
           Discussion forum. A thread is a subject; every post (opening post
           or reply) is a row in 'posts'. Deleted posts stay in memory with
           'deleted' => true for auditing and are simply not displayed.
           ---------------------------------------------------------------- */
        'threads' => [
            1 => ['title' => 'Is the Sentinel actually worth its points at 1500?', 'board' => 'rules', 'tag' => 'discussion', 'author' => 'toma', 'created' => '2026-08-10T08:22:00', 'views' => 612],
            2 => ['title' => 'Cork sheet versus insulation foam for cliffs', 'board' => 'terrain', 'tag' => 'question', 'author' => 'kaya', 'created' => '2026-07-30T19:05:00', 'views' => 287],
            3 => ['title' => 'Finished the whole first company — 62 models, eight months', 'board' => 'wip', 'tag' => 'showcase', 'author' => 'renn', 'created' => '2026-08-01T12:40:00', 'views' => 1904],
            4 => ['title' => 'Bone Ivory goes chalky over black primer — what am I doing wrong?', 'board' => 'painting', 'tag' => 'question', 'author' => 'oksana', 'created' => '2026-08-06T22:15:00', 'views' => 755],
            5 => ['title' => 'Errata thread: does overwatch trigger on a fall-back move?', 'board' => 'rules', 'tag' => 'question', 'author' => 'toma', 'created' => '2026-07-12T09:00:00', 'views' => 2311],
            6 => ['title' => 'Post your table layouts — I need ideas for a 4 by 4', 'board' => 'terrain', 'tag' => 'discussion', 'author' => 'kaya', 'created' => '2026-07-25T16:30:00', 'views' => 640],
        ],
        'posts' => [
            // Thread 1
            1 => ['thread_id' => 1, 'parent_id' => null, 'author' => 'toma', 'created' => '2026-08-10T08:22:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'Two walkers or a third infantry block?',
                  'body'  => "I have run the Sentinel in every list since the points update and I am no longer convinced. At 210 points it is close to a third infantry block, and the block scores objectives. The walker does not.\n\nIts gun is genuinely good — a reliable long-range shot that ignores light cover. But it only fires once per turn, and the moment anything closes to within twelve inches it is a very expensive shield-bearer that cannot hold ground.\n\nConvince me I am wrong. Specifically: what is it doing in the mid-game that a block of infantry with the same points is not doing better?",
                  'image' => 'assets/img/photo-sentinel-scale.svg', 'image_alt' => 'An assembled Sentinel walker photographed beside three infantry miniatures for scale, standing roughly twice their height.', 'caption' => 'My current build, next to the infantry it keeps getting compared to.'],
            2 => ['thread_id' => 1, 'parent_id' => 1, 'author' => 'kaya', 'created' => '2026-08-10T10:05:00', 'edited' => '2026-08-10T10:41:00', 'edit_reason' => 'fixed points value', 'deleted' => false,
                  'title' => 'It is a target-priority tax, not a scoring piece',
                  'body'  => "You are measuring it against objectives, which it was never going to win. Measure it against what your opponent does on turn one instead. In six of my last eight games the Sentinel ate the entire first round of anti-armour shooting, because leaving it alive is worse than shooting anything else.\n\nThat is 210 points buying a turn of near-total safety for your infantry. The block you want to add instead would be taking those hits, and blocks fold when they lose a third of their models.",
                  'image' => 'assets/img/photo-highlights.svg', 'image_alt' => "A close-up of a miniature's shoulder plate with a fine edge highlight running along the rim.", 'caption' => ''],
            3 => ['thread_id' => 1, 'parent_id' => 2, 'author' => 'toma', 'created' => '2026-08-10T11:47:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'That only holds against shooting-heavy lists',
                  'body'  => 'Fair, and I will concede the turn-one point. But against a close-assault list nobody shoots it at all — they just walk past and take the objectives while it plods after them. Then it is 210 points of scenery.',
                  'image' => '', 'image_alt' => '', 'caption' => ''],
            4 => ['thread_id' => 1, 'parent_id' => 2, 'author' => 'renn', 'created' => '2026-08-11T09:30:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'Then you are deploying it wrong',
                  'body'  => 'Against assault lists it should not be behind your line, it should be sitting on the objective you least want to contest. It cannot score, but it can occupy, and shifting it costs an assault army an entire combat phase.',
                  'image' => '', 'image_alt' => '', 'caption' => ''],
            5 => ['thread_id' => 1, 'parent_id' => 1, 'author' => 'oksana', 'created' => '2026-08-13T21:40:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'Nobody has mentioned the repair rule',
                  'body'  => "If you are already bringing a tech crew, the Sentinel is the only thing in the list they can repair. Two wounds back a turn changes the arithmetic considerably, and it is the reason I keep taking it.\n\nWithout the crew I agree with Toma. With the crew it is the best 210 points in the book.",
                  'image' => 'assets/img/photo-sentinel-sprue.svg', 'image_alt' => "An unbuilt plastic sprue showing the Sentinel's leg, torso and weapon components still attached to the frame.", 'caption' => 'Second one still on the sprue — the tech crew convinced me.'],
            // Thread 2
            6 => ['thread_id' => 2, 'parent_id' => null, 'author' => 'kaya', 'created' => '2026-07-30T19:05:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'Which one carves cleaner?',
                  'body'  => "I am building a set of cliffs for a 4 by 4 table and cannot decide between cork sheet and insulation foam. Cork has the texture already but crumbles at the edges; foam carves cleanly but needs a lot of work to stop looking like foam.\n\nWhat has held up on your tables?",
                  'image' => 'assets/img/terrain-ruins.svg', 'image_alt' => 'A ruined stone wall section with a broken archway, built as tabletop scenery.', 'caption' => 'The kind of edge I am trying to match.'],
            7 => ['thread_id' => 2, 'parent_id' => 6, 'author' => 'renn', 'created' => '2026-08-12T09:15:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'Foam core, cork skin',
                  'body'  => 'Do both. Carve the shape out of foam for the bulk, then glue torn cork over the faces you will see. You get the clean silhouette and the rock texture, and the cork stops the foam from denting.',
                  'image' => '', 'image_alt' => '', 'caption' => ''],
            // Thread 3
            8 => ['thread_id' => 3, 'parent_id' => null, 'author' => 'renn', 'created' => '2026-08-01T12:40:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'The whole company, finally',
                  'body'  => "Sixty-two models, eight months, one colour scheme kept consistent the whole way through. Two walkers, six infantry blocks and a command group.\n\nHappy to answer questions about batch painting — the only way this got done was doing every model's base coat in one sitting.",
                  'image' => 'assets/img/photo-army.svg', 'image_alt' => 'A rank of eight painted infantry miniatures photographed from the front on a neutral backdrop.', 'caption' => 'The front rank. The other fifty-four are behind them.'],
            9 => ['thread_id' => 3, 'parent_id' => 8, 'author' => 'oksana', 'created' => '2026-08-11T18:02:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'How did you keep the bone consistent?',
                  'body'  => 'The bone on the shoulder plates is identical across every model. Did you mix a big batch or is that straight from the pot?',
                  'image' => '', 'image_alt' => '', 'caption' => ''],
            // Thread 4
            10 => ['thread_id' => 4, 'parent_id' => null, 'author' => 'oksana', 'created' => '2026-08-06T22:15:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'Chalky finish every time',
                  'body'  => "Bone Ivory over black primer goes chalky and streaky on the second coat, every model. I am using it straight from the pot with a size 1. What am I doing wrong?",
                  'image' => 'assets/img/paint-bone.svg', 'image_alt' => 'A hexagonal paint pot with a pale bone-coloured label beside a painted swatch card.', 'caption' => ''],
            11 => ['thread_id' => 4, 'parent_id' => 10, 'author' => 'renn', 'created' => '2026-08-09T14:28:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'Thin it, and go over a grey base first',
                  'body'  => 'Straight from the pot is the problem. Thin it two parts water to one part paint and build it in three passes. Over black, put a mid-grey down first so the bone is not doing all the covering on its own.',
                  'image' => '', 'image_alt' => '', 'caption' => ''],
            // Thread 5
            12 => ['thread_id' => 5, 'parent_id' => null, 'author' => 'toma', 'created' => '2026-07-12T09:00:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'Overwatch and fall-back: the wording',
                  'body'  => "Page 41 says overwatch triggers when an enemy unit \"ends a move within 12 inches\". Page 58 says a fall-back move is \"a move made in the movement phase\". So does a unit falling back through 12 inches trigger overwatch, or is fall-back excluded because it starts in combat?\n\nQuote the page if you answer, please.",
                  'image' => 'assets/img/rulebook.svg', 'image_alt' => 'A closed hardback rulebook with an embossed anvil emblem on the cover.', 'caption' => ''],
            13 => ['thread_id' => 5, 'parent_id' => 12, 'author' => 'kaya', 'created' => '2026-08-07T11:53:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'The 2026 errata settles it',
                  'body'  => 'Errata 1.2, page 3: "Overwatch may not be fired at a unit making a fall-back move." It is in the third-edition reprint too, folded into page 41.',
                  'image' => '', 'image_alt' => '', 'caption' => ''],
            // Thread 6
            14 => ['thread_id' => 6, 'parent_id' => null, 'author' => 'kaya', 'created' => '2026-07-25T16:30:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'Show me your 4 by 4 layouts',
                  'body'  => 'Our club is moving to 4 by 4 tables for the league and I have run out of layout ideas that are not "ruin in each corner". Photographs welcome, especially anything with a road or river.',
                  'image' => 'assets/img/photo-table.svg', 'image_alt' => 'A gaming table seen from above with terrain laid out and two forces deployed on opposite edges.', 'caption' => 'Current default layout. It is fine. It is boring.'],
            15 => ['thread_id' => 6, 'parent_id' => 14, 'author' => 'toma', 'created' => '2026-08-04T20:11:00', 'edited' => null, 'edit_reason' => '', 'deleted' => false,
                  'title' => 'Diagonal road, offset ruins',
                  'body'  => 'Run a road corner to corner and put the two big ruins on opposite sides of it, off-centre. Neither deployment zone gets a clean lane and the road becomes the objective everyone fights over.',
                  'image' => '', 'image_alt' => '', 'caption' => ''],
        ],

        /* ------------------------------------------------------------------
           Blog
           ---------------------------------------------------------------- */
        'blog' => [
            1 => ['title' => 'Edge highlighting without the chalky finish', 'author' => 'renn', 'date' => '2026-08-02', 'updated' => '2026-08-09', 'tags' => ['painting', 'beginner'], 'allow_comments' => true,
                  'summary' => 'Most edge highlights read as chalk because the last pass is too opaque and too wide. Here is the thinning ratio I use and where to stop.',
                  'body' => "An edge highlight is not a line drawn on an edge. It is a claim about where the light is coming from, and it only works if you make the same claim on every model in the unit.\n\nThin further than you think. The single most common problem is paint straight from the pot. At full opacity the highlight sits on top of the surface as a stripe. Thin it until it looks alarmingly transparent — roughly two parts water to one part paint — and build the brightness over two or three passes instead.\n\nLoad the brush properly. Bring the paint to a point on the palette, then wipe most of it off. You want the paint sitting in the belly of the brush and only a trace at the tip. A size 1 with a good point will do finer work than a size 0 that has splayed.\n\nOnly highlight what the light would hit. Pick a direction — top-left is conventional — and highlight only the edges facing it. Highlighting every edge flattens the model rather than defining it.\n\nKnow when to stop. Put the model at arm's length under normal room light. If the highlight is the first thing you see, it is too bright.",
                  'image' => 'assets/img/photo-highlights.svg', 'image_alt' => "A close-up of a miniature's shoulder plate with a fine edge highlight running along the rim, catching the light along one edge only.", 'caption' => 'The finished edge: bright along the top rim, absent underneath.'],
            2 => ['title' => 'A basing recipe that takes four minutes a model', 'author' => 'kaya', 'date' => '2026-07-24', 'updated' => null, 'tags' => ['basing', 'beginner'], 'allow_comments' => true,
                  'summary' => 'Bases are where an army stops looking like loose models. This is the fastest recipe I have found that still stands up close.',
                  'body' => "Bases are where an army stops looking like loose models and starts looking like an army. This recipe is four minutes a model once the glue is out.\n\nOne: PVA and fine sand, pressed down with a finger. Two: a dark brown base coat, straight over the sand. Three: a heavy drybrush of bone. Four: a pinch of static grass on the side the light comes from.\n\nThe trick is doing all forty at each step before moving to the next. Nothing about it is clever; the speed comes from never picking up the same model twice for the same job.",
                  'image' => 'assets/img/photo-basing.svg', 'image_alt' => 'Three finished bases side by side showing gravel, static grass and a broken paving slab.', 'caption' => 'Left to right: sand only, drybrushed, finished.'],
            3 => ['title' => 'Priming in humid weather, and why yours went furry', 'author' => 'renn', 'date' => '2026-07-11', 'updated' => null, 'tags' => ['painting'], 'allow_comments' => true,
                  'summary' => 'Spray primer fails in a specific and predictable way above about seventy per cent humidity. Here is what is happening and how to recover a furry model.',
                  'body' => "Spray primer dries in the air before it reaches the model when the humidity is high enough. The droplets land as dust and the model comes out with a texture like fine sandpaper.\n\nAbove about seventy per cent humidity, do not spray. If you already have, a soft toothbrush and warm water takes most of it off; anything left can be sanded flat with a fine sponge.\n\nOn a humid day, brush-on primer is slower but it never fails this way.",
                  'image' => 'assets/img/photo-primer.svg', 'image_alt' => 'A miniature primed in flat grey, standing on a cork block before any colour is applied.', 'caption' => ''],
            4 => ['title' => 'Building a ruined block from one sheet of foam', 'author' => 'kaya', 'date' => '2026-06-29', 'updated' => null, 'tags' => ['terrain'], 'allow_comments' => true,
                  'summary' => 'One A2 sheet, a bread knife and an evening gets you four modular wall sections that pack flat.',
                  'body' => "One A2 sheet of 25 mm insulation foam is enough for four wall sections, each 15 cm long. Cut the outline with a bread knife, press brick lines in with a ballpoint pen, and knock the corners about with a ball of foil so nothing is perfectly straight.\n\nSeal with watered-down PVA before painting, or spray paint will melt it.",
                  'image' => 'assets/img/terrain-ruins.svg', 'image_alt' => 'A ruined stone wall section with a broken archway and rubble at its base.', 'caption' => ''],
            5 => ['title' => 'Winter open: what actually won, and what did not', 'author' => 'toma', 'date' => '2026-06-15', 'updated' => null, 'tags' => ['events'], 'allow_comments' => true,
                  'summary' => 'Thirty-two players, five rounds, and a top table nobody predicted. The full breakdown with lists.',
                  'body' => "Thirty-two players over five rounds. The top table was two infantry-heavy lists with no walkers at all, which is not what anyone predicted after the points update.\n\nWhat won: objectives held on turn three. What did not: anything that spent turn one shooting the biggest model on the other side of the table.",
                  'image' => 'assets/img/photo-table.svg', 'image_alt' => 'A gaming table seen from above with terrain laid out and two forces deployed on opposite edges.', 'caption' => 'Table one, round five.'],
            6 => ['title' => 'Setting up a desk you will actually sit at', 'author' => 'oksana', 'date' => '2026-05-30', 'updated' => null, 'tags' => ['beginner'], 'allow_comments' => true,
                  'summary' => 'Light, height and where you put the water pot. Three cheap changes that doubled how often I paint.',
                  'body' => "Light first: a daylight lamp on an arm, positioned so your hand does not shadow the model. Height second: the desk should let you rest both elbows. Water pot third: on the side of your painting hand, so you never reach across a wet model.\n\nNone of these cost more than a pot of paint and together they doubled the number of evenings I actually sat down.",
                  'image' => 'assets/img/photo-workbench.svg', 'image_alt' => 'A painting workbench with brushes in a jar, open paint pots and a desk lamp angled over a cutting mat.', 'caption' => ''],
        ],
        'comments' => [
            1 => ['post_id' => 1, 'author' => 'toma', 'created' => '2026-08-02T14:12:00', 'body' => 'The bit about only highlighting upward edges is what finally made mine stop looking like line art. I had been outlining everything for two years.'],
            2 => ['post_id' => 1, 'author' => 'kaya', 'created' => '2026-08-03T09:40:00', 'body' => 'Two parts water to one part paint felt wrong until I tried it on a test model. Three passes and it looks far better than one thick one. Does the ratio change for metallics?'],
            3 => ['post_id' => 1, 'author' => 'renn', 'created' => '2026-08-03T11:05:00', 'body' => 'Metallics need less thinning — the flake settles out if you push it too far. Closer to one to one, and stir between passes.'],
            4 => ['post_id' => 2, 'author' => 'oksana', 'created' => '2026-07-25T08:30:00', 'body' => 'Did forty bases in an evening with this. The static grass step is the one that makes it look finished.'],
            5 => ['post_id' => 3, 'author' => 'kaya', 'created' => '2026-07-12T19:20:00', 'body' => 'The toothbrush trick saved a whole squad. Thank you.'],
        ],

        /* ------------------------------------------------------------------
           Reviews
           ---------------------------------------------------------------- */
        'reviews' => [
            1 => ['product_id' => 'oxide', 'author' => 'oksana', 'rating' => 4, 'date' => '2026-07-28', 'bought' => '2026-07-09', 'helpful' => 42,
                  'title' => 'Oxide Wash does one job and does it properly',
                  'body' => "I bought two pots to get through a squad of eight vehicles, and I have used about a pot and a half. That is a fair test, so here is what it actually does.\n\nFlow is the part that matters and the part cheaper washes get wrong. It runs into panel lines and rivet recesses and stays there, without pooling in the middle of a flat plate and drying as a tidemark.\n\nDrying time is about twenty minutes to touch-dry in a warm room, and closer to an hour before I would drybrush over it. That is slower than I expected, and it is the reason this is not a five-star review.\n\nIt is considerably better over gloss than over matt. A quick gloss coat first and the faint halo you get on matt goes away entirely.",
                  'image' => 'assets/img/photo-army.svg', 'image_alt' => 'A rank of eight painted vehicles photographed from the front, each showing darkened panel lines where the wash has settled.', 'caption' => 'The squad the two pots went on.'],
            2 => ['product_id' => 'brushes', 'author' => 'kaya', 'rating' => 5, 'date' => '2026-08-05', 'bought' => '2026-02-01', 'helpful' => 17,
                  'title' => 'Still holding a point after six months',
                  'body' => "I have wrecked cheaper brushes in a month. These have done a whole army and the size 1 still comes to a point straight out of the water.\n\nClean them with brush soap and never leave them tip-down in the pot and they will outlast anything else on the desk.",
                  'image' => '', 'image_alt' => '', 'caption' => ''],
            3 => ['product_id' => 'bone', 'author' => 'toma', 'rating' => 3, 'date' => '2026-07-19', 'bought' => '2026-07-02', 'helpful' => 9,
                  'title' => 'Covers well, but you have to thin it',
                  'body' => "Straight from the pot it goes chalky over black primer, exactly as the forum thread warned. Thinned properly it is fine, but three stars because the pot does not say so.",
                  'image' => '', 'image_alt' => '', 'caption' => ''],
            4 => ['product_id' => 'sentinel', 'author' => 'renn', 'rating' => 4, 'date' => '2026-07-02', 'bought' => '2026-06-20', 'helpful' => 23,
                  'title' => 'Excellent kit, fiddly ankle joint',
                  'body' => "Push-fit everywhere except the ankles, which need clamping while the cement sets or the model leans. Worth knowing before you start rather than after.\n\nThe head options are all good and the sprue has enough spare parts to convert a second model.",
                  'image' => 'assets/img/photo-sentinel-sprue.svg', 'image_alt' => 'The unbuilt Sentinel sprue with the ankle pieces still attached to the frame.', 'caption' => 'The ankle pieces, bottom right, are the ones to watch.'],
            5 => ['product_id' => 'oxide', 'author' => 'kaya', 'rating' => 5, 'date' => '2026-06-12', 'bought' => '2026-05-30', 'helpful' => 11,
                  'title' => 'The only wash I keep two of',
                  'body' => "One pot on the desk, one in the drawer. It does bare metal, it does weathered plate, and thinned with medium it does a passable rust glaze. Nothing else I own is that flexible.",
                  'image' => '', 'image_alt' => '', 'caption' => ''],
            6 => ['product_id' => 'rulebook', 'author' => 'oksana', 'rating' => 4, 'date' => '2026-05-20', 'bought' => '2026-05-02', 'helpful' => 5,
                  'title' => 'Errata folded in, which is the whole point',
                  'body' => "Buying the third edition just to have the errata in the text rather than on a printout is worth it on its own. The binding lies flat, the ribbon is useful, and the points appendix is the first one that has been laid out sensibly.",
                  'image' => '', 'image_alt' => '', 'caption' => ''],
            7 => ['product_id' => 'scarab', 'author' => 'toma', 'rating' => 2, 'date' => '2026-04-08', 'bought' => '2026-03-28', 'helpful' => 3,
                  'title' => 'Legs are fragile at 15 mm',
                  'body' => "Two legs snapped coming off the sprue and a third during assembly. The poses are good and the bases are nice, but at this scale the leg pieces are too thin for plastic.",
                  'image' => '', 'image_alt' => '', 'caption' => ''],
        ],

        /* ------------------------------------------------------------------
           Carts (per user) and completed orders
           ---------------------------------------------------------------- */
        'carts' => [
            'kaya' => [
                'promo' => null,
                'items' => [
                    ['id' => 1, 'product_id' => 'sentinel', 'variant' => 'cannon', 'quantity' => 1, 'note' => ''],
                    ['id' => 2, 'product_id' => 'oxide', 'variant' => '30', 'quantity' => 1, 'note' => ''],
                    ['id' => 3, 'product_id' => 'brushes', 'variant' => '012', 'quantity' => 1, 'note' => ''],
                ],
            ],
        ],
        'orders' => [],
    ];
}
