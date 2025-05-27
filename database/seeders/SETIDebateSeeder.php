<?php

namespace DatabaseSeeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Room;
use App\Models\Debate;
use App\Models\DebateMessage;
use App\Models\DebateEvaluation;
use Carbon\Carbon;

class SETIDebateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // デモ用ユーザーを作成（既に存在する場合はスキップ）
        $affirmativeUser = User::firstOrCreate(
            ['email' => 'demo1@example.com'],
            [
                'name' => 'Takapon',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_guest' => true,
                'guest_expires_at' => Carbon::now()->addHours(24),
            ]
        );

        $negativeUser = User::firstOrCreate(
            ['email' => 'demo2@example.com'],
            [
                'name' => 'Hiroyuki',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_guest' => true,
                'guest_expires_at' => Carbon::now()->addHours(24),
            ]
        );

        // SETIディベートを作成
        $this->createSETIDebate($affirmativeUser, $negativeUser);
    }

    private function createSETIDebate($affirmativeUser, $negativeUser)
    {
        $createdAt = Carbon::now()->subDays(25);

        // ルーム作成
        $room = Room::create([
            'name' => 'Should the United States Government Significantly Increase Space Exploration?',
            'topic' => 'Demo: Should the United States Government Significantly Increase Exploration and/or Development of Space Beyond the Earth\'s Mesosphere?',
            'remarks' => 'Demo Debate',
            'status' => Room::STATUS_FINISHED,
            'language' => 'english',
            'format_type' => 'custom',
            'custom_format_settings' => [
                "1" => [
                    "name" => "First Affirmative Constructive",
                    "speaker" => "affirmative",
                    "duration" => 480,
                    "is_prep_time" => false,
                    "is_questions" => false
                ],
                "2" => [
                    "name" => "First Negative Constructive",
                    "speaker" => "negative",
                    "duration" => 480,
                    "is_prep_time" => false,
                    "is_questions" => false
                ],
                "3" => [
                    "name" => "Second Affirmative Constructive",
                    "speaker" => "affirmative",
                    "duration" => 480,
                    "is_prep_time" => false,
                    "is_questions" => false
                ],
                "4" => [
                    "name" => "Second Negative Constructive",
                    "speaker" => "negative",
                    "duration" => 480,
                    "is_prep_time" => false,
                    "is_questions" => false
                ],
                "5" => [
                    "name" => "First Negative Rebuttal",
                    "speaker" => "negative",
                    "duration" => 300,
                    "is_prep_time" => false,
                    "is_questions" => false
                ],
                "6" => [
                    "name" => "First Affirmative Rebuttal",
                    "speaker" => "affirmative",
                    "duration" => 300,
                    "is_prep_time" => false,
                    "is_questions" => false
                ],
                "7" => [
                    "name" => "Second Negative Rebuttal",
                    "speaker" => "negative",
                    "duration" => 300,
                    "is_prep_time" => false,
                    "is_questions" => false
                ],
                "8" => [
                    "name" => "Second Affirmative Rebuttal",
                    "speaker" => "affirmative",
                    "duration" => 300,
                    "is_prep_time" => false,
                    "is_questions" => false
                ]
            ],
            'evidence_allowed' => true,
            'created_by' => $affirmativeUser->id,
            'is_ai_debate' => false,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // ユーザーをルームに参加させる
        $room->users()->attach([
            $affirmativeUser->id => ['side' => 'affirmative'],
            $negativeUser->id => ['side' => 'negative'],
        ]);

        // ディベート作成
        $debate = Debate::create([
            'room_id' => $room->id,
            'affirmative_user_id' => $affirmativeUser->id,
            'negative_user_id' => $negativeUser->id,
            'current_turn' => 100,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // 8つのスピーチを順番に追加
        $this->createDebateMessages($debate, $createdAt);

        // 評価を作成
        $this->createDebateEvaluation($debate);

        return $debate;
    }

    private function createDebateMessages($debate, $baseTime)
    {
        $messageIndex = 0;

        // Turn 1: Affirmative First Constructive (1AC)
        $this->addAffirmativeFirstConstructive($debate, $baseTime, $messageIndex);

        // Turn 2: Negative First Constructive (1NC)
        $this->addNegativeFirstConstructive($debate, $baseTime, $messageIndex);

        // Turn 3: Affirmative Second Constructive (2AC)
        $this->addAffirmativeSecondConstructive($debate, $baseTime, $messageIndex);

        // Turn 4: Negative Second Constructive (2NC)
        $this->addNegativeSecondConstructive($debate, $baseTime, $messageIndex);

        // Turn 5: Negative First Rebuttal (1NR)
        $this->addNegativeFirstRebuttal($debate, $baseTime, $messageIndex);

        // Turn 6: Affirmative First Rebuttal (1AR)
        $this->addAffirmativeFirstRebuttal($debate, $baseTime, $messageIndex);

        // Turn 7: Negative Second Rebuttal (2NR)
        $this->addNegativeSecondRebuttal($debate, $baseTime, $messageIndex);

        // Turn 8: Affirmative Second Rebuttal (2AR)
        $this->addAffirmativeSecondRebuttal($debate, $baseTime, $messageIndex);
    }

    private function addAffirmativeFirstConstructive($debate, $baseTime, &$messageIndex)
    {
        $message = <<<'MESSAGE'
We support the resolution.

**Observation: The Search for Extraterrestrial Intelligence (SETI) is Justified**

Unlike the popular science fiction movie E.T., the search for extraterrestrial intelligence is a respected scientific endeavor. Richard Berendzen of American University stated in 1979 that "today, the serious scientific search for extraterrestrial life commands the attention and respect of many of our most prominent, careful, and judicious scientists. SETI (Search for Extra Terrestrial Intelligence) in its sophisticated, modern form is solid and sober, not tawdry or sensational." The New York Times further substantiated this in 1982, reporting that "last October, 69 scientists, including seven Nobel laureates, from a dozen countries published a letter in the journal Science urging 'organization of a coordinated, worldwide and systematic search for extraterrestrial intelligence.'"

This impressive scientific respectability accorded to SETI exists due to the almost certain probability that intelligent extraterrestrial civilizations populate the universe. Dennis Meredith of the California Institute of Technology argued in 1979 that "so many stars are in the universe—100 billion at last count in our galaxy alone—that statistics almost dictate that life does exist elsewhere." Professor Frank Drake of Cornell University concluded in 1981 that "the current consensus concerning the abundance of extraterrestrial life is that it exists in abundance in the Universe."

Unfortunately, present SETI efforts are inadequate, as we explain in:

**Contention I: Radio Frequency Interference Precludes SETI**

While NASA has allocated $1.5 million for a SETI listening program scheduled to begin operation in 1988, radio frequency interference in the spectrum threatens to obstruct the efficacy of the search.

(A) The magnitude of this problem was explained by the Christian Science Monitor in 1982: "Earth's atmosphere is growing so clogged with man-made radio noise—from satellites to television signals—that within 10 to 20 years it may be nearly impossible for an earthbound receiver to detect radio signals from some other intelligent source in the galaxy." In fact, the interference is already so significant that scientists fear we may have already missed detecting signals from extraterrestrials. The New York Times Magazine reported in 1977 that "the situation is serious enough that some scientists wonder whether their radiotelescopes already have received intelligent extraterrestrial signals that they failed to recognize as such."

(B) As the level of interference increases, the problem will steadily worsen. Scientists Edelson and Levy stated in 1980 that "radio frequency interference (RFI) will be an important limitation on the search. Because of the sensitivity required of a SETI receiver, virtually any radiation picked up in the receiver bandwidth will make the conduct of this search more difficult." Even new technological advancements will not be able to overcome the interference problem. Library of Congress science writer Marcia Smith argued in 1977 that "if the present pace continues, receivers may not be able to distinguish between man-made interference and a signal arriving from space, regardless of how advanced radio telescope technology becomes."

(C) Currently, no portion of the microwave spectrum is reserved for SETI listening. Scientists must utilize the same limited frequency bands reserved for radio astronomy. If the "waterhole" band is not reserved for SETI listening, a reliable search for extraterrestrials will be impossible. Professors Billingham and Pešek argued in 1982 that "SETI observers will use the rather narrow frequency bands set aside for radio astronomy, in which there is no allocation for transmissions; however, they really need access to much broader protected frequency bands for comprehensive exploration of the spectrum."

**Contention II: SETI is Advantageous**

(A) The objective of listening for extraterrestrials is to detect a message from an advanced civilization. Astronomer Carl Sagan of Cornell explained why such a civilization would transmit signals in 1978: "A civilization which had been aided by the receipt of such a message in its past might wish to similarly benefit other emerging technical societies. The amount of power that need be expended in interstellar radio communication should be a tiny fraction of what is available for a civilization only slightly more advanced than we, and such radio transmission services could be an activity either of an entire planetary government or of relatively small groups of hobbyists, amateur radio operators and the like."

While science fiction writers have recited gruesome tales of extraterrestrials traveling to Earth on exploitative missions, serious scientists dismiss such accounts as mere fantasy. Science authors Robert Rood and James Trefil explained in 1981 that "the galaxy is so large that civilizations will be isolated from each other by huge distances. This is one reason the scientists at Green Bank considered that even though communication between ETI's is plausible, visitation is not." The only type of communication we can expect, then, will be informational, analogous to our reception of information from ancient civilizations here on Earth. Scientists Billingham and Pešek explained in 1982 that "any message in such a transmission would be a message between cultures, not between individual persons. Human analogies are evident here; there is a long-continued interest in great books from the past. The Greek philosophers are studied afresh by each generation, without any hope of interrogating Socrates or arguing with Aristotle."

(B) Any signal detected would be from a civilization older and more advanced than our own culture. Professor Robert Jastrow of Dartmouth College argued in 1980 that "life on other worlds is not only billions of years older than man, but also billions of years beyond him in intelligence." The potential exists to learn great scientific secrets from listening to an extraterrestrial signal. Soviet scientist A.I. Ioirysh explained in 1979 that "discovery of the first alien civilization may have stupendous importance for the scientific and technological progress of humanity." Cornell astronomer Frank Drake concurred in 1976: "Interstellar contact would undoubtedly enrich our civilization with scientific and technical information which we could obtain alone only at very much greater expense."

Carl Sagan documented the potential benefits in 1979: "It is possible that an early message may contain detailed prescriptions for the avoidance of technological disaster, for a passage through adolescence to maturity. Perhaps the transmissions from advanced civilizations will describe which pathways of cultural evolution are likely to lead to the stability and longevity of an intelligent species, and which other paths lead to stagnation or degeneration or disaster. Perhaps there are straight-forward solutions, still undiscovered on Earth to problems of food shortages, population growth, energy supplies, dwindling resources, pollution and war." Scientists Timothy Healy of the University of Santa Clara and Mark Stull of the Ames Research Center concurred with this in 1981: "We know that our own civilization is capable of destroying itself through wars, overpopulation, pollution and other causes. If we were to find another civilization (that would very likely be more advanced than we), this would be evidence that it is at least possible for a civilization to attain maturity without destroying itself. This might provide the impetus we need to redouble our efforts to eliminate such possibilities. Perhaps the signals themselves would tell us how to solve these problems."

(C) Our very survival might depend upon detecting such a signal. Scientist John Kraus concluded in 1982 that "a message from an extinct civilization might be the very one we need most. Learning why the civilization became extinct could help us from becoming extinct also." Even a negative finding could have beneficial effects upon society. Carl Sagan argued in 1979 that "such a finding (the absence of advanced extraterrestrials) will stress as perhaps nothing else can our responsibilities to future generations: because the most likely explanation of negative results, after a comprehensive and resourceful search, is that societies destroy themselves before they are advanced enough to establish a high-power radio transmitting service. Thus, organization of a search for interstellar radio messages, quite apart from the outcome, is likely to have a cohesive and constructive influence on the whole of the human condition."

(D) Simply listening and decoding the signals will enable us to receive the benefits. Scientist Robert Dixon stated in 1982 that "no one is proposing to reply to any message at all. There is no need to. We would reap most of the benefits by just receiving a single signal, thereby proving that man is not alone and unique in the Universe. Then if we could go one step further and understand the message contents of the signal, we would again reap most of the remaining benefits."

(E) Fortunately, it will be possible to decode the signal. Carl Sagan argued in 1983 that "because the laws of nature are the same everywhere, science itself should provide a means of communication even between beings that are physiologically very different. I suspect that the decrypting of the message, if we are so fortunate as to receive one, will be much simpler than its acquisition."

**Plan**

In order to enhance our ability to detect an extraterrestrial signal, we advocate the adoption of the following plan, to be implemented through minimally sufficient legislative means:

Plank One: Mandate. The federal government will increase the exploration of outer space by reserving the frequency band from 1400 to 1727 megahertz for the search for extraterrestrial intelligence. Systems currently utilizing these bands will be shifted to alternative frequencies as feasible.

Plank Two: Ancillary Provisions. Enforcement will be through existing means. Legislative intent will be based on affirmative speeches.

**Contention III: Protecting the Waterhole Enhances SETI**

Prohibiting future use and transferring existing systems to alternate frequencies could effectively preserve the "waterhole" for SETI listening. Scientists Mark Stull and Charles Seeger of the Ames Research Center explained in 1979 that "if prompt action is taken now, a plan can be developed and implemented that will protect the waterhole while minimizing the impact on satellite services now in the band. Such a plan should bar new satellite systems from the waterhole and provide a timetable for those currently operational or in advanced stages of design to obtain alternate frequencies."

(A) Systems could be moved to other frequencies without adverse consequences. Bernard Oliver of Hewlett-Packard Research argued in 1979 that "the proposed services can be shifted to other frequencies without appreciable loss of effectiveness but, if the rationale for the waterhole is correct, the search for intelligent extraterrestrial life cannot."

(B) Protecting the waterhole is vital since this frequency band is the place extraterrestrials would most likely broadcast their signals. Stull and Seeger explained in 1979 that "it is possible that extraterrestrial societies may deliberately choose this band for transmissions meant for detection by civilizations unknown because they, too, would realize not only that it lies in the frequency range where information transmission is most efficient, but also that it contains the prominent interstellar spectral lines of hydrogen and hydroxyl, the decomposition products of water."

(C) Furthermore, Earth scientists have the best opportunity to detect extraterrestrial signals in the waterhole band because this frequency has the least amount of interference from interstellar noise sources. Stull and Seeger stated in 1979 that "the frequency band from 1400 to 1727 megahertz (MHz) is of unique value. Signals from other civilizations might be broadcast at any frequency, but it is within roughly this small fraction of the spectrum that we have the greatest ability to detect one." Scientist A. L. Berman concluded in 1980 that "in any SETI search scheme, the waterhole becomes an a priori 'preferred' region for observation."

(D) If we enable systematic SETI efforts to be conducted in the waterhole, we could detect extraterrestrial signals within a few decades. Professor Michael Papagiannis of Boston University concluded in 1980: "If we could make, therefore, a serious commitment to such a program, it is reasonable to expect that in a few decades, and possibly by the year 2001 as prophesized by Arthur Clarke, we will know if our galaxy has been colonized or not."

Given the benefits of receiving a signal from an extraterrestrial civilization, there is but one choice: vote affirmative and preserve the waterhole from radio frequency interference.
MESSAGE;

        $this->createMessage($debate, $debate->affirmative_user_id, $message, 1, $baseTime, $messageIndex);
    }

    private function addNegativeFirstConstructive($debate, $baseTime, &$messageIndex)
    {
        $message = <<<'MESSAGE'
**Topicality Observations:**

First, the plan must traverse space. This is a fundamental parameter and clarification of the topic. In other words, people wouldn't have voted for the topic if they thought it was merely Arthur C. Clarke ruminating in their room. As a subpoint, you need to have any kind of space exploration at all. L5 News in January/February of 1985 states: "To explore space, to know space, we must go out into space..."

Second, the plan must only be an effect. Today, we're spending millions, right? If they can nail things topically, then anything will be topical, right? Because we could keep people alive. Puttkamer in 1985 states: "None of large endeavors that I have in mind will be done unless we first address our foremost problems here on earth—hunger, illiteracy, disease, and poverty." In other words, you can address problems on Earth, and that would yield the same effects. That's why their interpretation is flawed.

Third argument: an interactive element. There are economic advances. Rossi and Plano in 1980 state: "Economic advancement sought through national or regional programs based on a single plan or on a related series of plans."

Fourth argument: Standards.
A. Reasonability is insufficient. Webster's defines reasonability in a way that would make every feasible action topical.
B. Field consciousness is key. Scientists will define the discipline and its meaning.
C. Grammatical consciousness is key. The definition of words is crucial.
D. Each word must have meaning. The problem is to be assumed by the affirmative. There should be no superfluity.

Fifth subpoint: Topicality is a voting issue.
(a) It is a dictum. The NDT suggests you should uphold it.
(b) Education. I suggest that by increasing the broadness of the topics, you decrease the amount of education.
(c) Jurisdiction. You cannot vote for the affirmative unless you are sure of exactly what they are doing.

**Case Side:**

On the observation: They read a card from '79 Science Digest.

First argument: They lied. That will be elaborated on below.

Second argument: This points out the problem. They say it has to be coordinated, and they don't prove that they are going to be coordinated. John asked this in cross-examination.

**On Inherency (Contention I):**

First argument: You can cope with interference now. Morrison in 1983 states: "I suspect the techniques for discriminating the interference will get better. You'll lose five or ten percent of the sky, but the next year you'll regain ten percent. It's a shifting kind of barrier. It's like the appearance of clouds in a telescope. No astronomer desires it, but they can all cope with it. And I think it's much the same thing."

Second argument: Low-power uses are compatible. Cosmic Search in January 1979 states: "Many low power uses of the band are compatible with SETI provided the transmitters are not in satellites."

Third argument: The waterhole is clear. Futurist in 1979 provides much more recent evidence: "The water hole... is relatively free of obstructing emissions from Earth and outer space..."

Fourth subpoint: Amateurs do not block now. Delta Vee in 1981 states: "Large existing systems were built to search a definite range of frequencies. For this reason, frequencies below about 1.2 GHz or 1.2 billion cycles per second are little used and are largely wide-open for amateur searches. Earth has been leaking strong electromagnetic transmissions in these frequencies from T.V. and radars into space for several decades. These radiations, traveling at the speed of light, have now reached several hundred of the nearby stars. It is very possible that the first identifiable intelligent signals will be in these frequencies."

Next argument: Plan Meets Need (PMN). Earth will always block it, and that's the real problem. Scientific Reporter in 1981 states: "The ionosphere is a natural emitter of radio emissions in the Kilohertz, the low megahertz range of the radio frequency spectrum."

**On the First Advantage (SETI is Advantageous):**

First of all, technology is a bad idea. We suggest that you shouldn't listen to them at all.

Second argument: They will lie. They have an incentive to do so. Hubbard in 1981 states: "The elitism of science enhances its mystifying power. The scientific elite not only helps to generate the mystification, it also stands to gain the most from it and therefore is its most willing victim." Scientists lie, and they're abused, and they misinterpret things. We're arguing that they'll mystify all the information we know about the universe. They'll gain an elite, and that's bad. That's the argument of the Greek philosophers, so obviously that's a harm for anyone who's ever taken a Greek test in their life.

The Healy 1981 card:
(a) First of all, the message would be really old, right?
(b) And we wouldn't know whether they died.

The next card [Kraus] which says that even if they're extinct:
(a) First of all, that seems really questionable. If they're extinct, they've done something bad. Why should we listen to their information?
(b) Second of all, it doesn't mean that they're going to get the information after they've known that they're going to become extinct, right? They could just say, "Oh, this is the way to do it. Tech, tech, tech," and we'll say, "Good," and we'll die.
(c) Third argument: Listening is analogous to listening to the devil. You shouldn't do it. Maybe it'll inform you about what to do wrong, but it is bad, because it will poison your mind.

Sagan in 1971: First of all, empirically that's not true. We've had thousands of years of failures, and that doesn't convince us of anything about the Earth. That's why the argument is wrong.

Second Sagan card, at the bottom in 1983: Laws of nature, right? About translating it. That's hardly true, right? We have the Rosetta Stone, and we didn't know what to do with it unless we had cryptography.

The arguments down below on solvency: I will grant them.

**Underviews:**

**A. First general argument: They increase science.** That is within their first affirmative constructive. More comes from Planetary Report in 1982: "We believe such a coordinated search program is well-justified on its scientific merits. It will also have important subsidiary benefits for radio astronomy in general." Black in '83 states: "Data from a search for other planetary systems are essential to understanding the origin of our solar system. Without these data, we will be confined to conjecture as to how the solar system formed, unable either to check our current hypotheses or to formulate others should our present views prove to be incorrect." Also from Black in '77: "Radar astronomy and deep space communications: Data processing techniques including the strong capabilities in spectrum analysis will be very useful for deep space communications, permitting multiple spacecraft monitoring with small antennas, thus releasing larger aperture facilities from routine tasks. In addition, the application of this technology to monitoring radio frequency interference will greatly assist deep space operations in our crowded spectral environment." These are tech arguments, right? Independent. SETI in 1977 states: "We expect to derive spin-off benefits of no small significance."

Now, the next series of arguments—I'm going to number these. These are science bad.

First argument: Science is fraudulent. Broad and Wade in 1982 states: "Fraud is merely evidence that science flies on the wings of rhetoric as well as reason."

Second argument: Errors destroy validity. Sabine in 1985 states: "Such a situation deserves our serious attention. The entire fabric of modern scientific method depends upon the inherent responsibility of published results... Such detail is now frequently lacking."

Third argument: Total disregard for truth. Morgenthau in 1972 states: "To the degree that science finds its meaning in service to the government, it is threatened with the loss of what constitutes it as a distinctive human activity: the distinction between true and false as its central concern. Although this concern is central to science, it is not central to politics."

Fourth argument: No benefits remain. Alexandria Gazette in 1984 states: "Look at almost any new technological development—put it into perspective by considering what the visionaries of 25 years ago would have thought of it, and then ask yourself if it really makes any difference in your daily life. I think few of our modern 'marvels' have. The bottom line is that our science is more advanced than ever, but life hasn't changed. We may have devices to toast our bread in milli-seconds, but when we drop it it still lands jelly side down."

Fifth argument: Infinite regression. Verschuur in '78 states: "The next step will be recognized for what it is, just another step on an endless journey. Particle physicists are already aware that perhaps there is no such thing as an end to their search for the smallest, most basic particle. Astronomers are beginning to talk of infinite universes. So where do we stop, or slow down?"

Sixth argument: Ignores real innovation. Morgenthau in '72 states: "The history of science is replete with instances of great discoveries having been passed over in silence or having been shoved aside by specious refutation and of technological developments having thus been retarded."

Seventh argument: Science is myth. Morris in 1980 states: "If the practice of science is to be described in a way that is at all accurate, one must speak of phenomena which are not understood, of theories which do not seem to quite work, areas in which there is not yet enough experimental data to formulate any theories at all."

Eighth argument: Pure hypocrisy. Morgenthau in '72 states: "Scientific terminology and argumentation become a kind of ideological convention through which antagonistic groups converse with each other, justifying and rationalizing their respective positions and interests and denying justification and rationality to those of the other side."

Ninth argument: Immoral. Morgenthau in '72 states: "The moral condition of mankind has deteriorated, and the program of science has decisively contributed to that deterioration."

Tenth argument: Destroys individual autonomy. Morgenthau in '72 states: "Science has already destroyed that realm of inner freedom through which the individual could experience his autonomy by controlling, however precariously, the narrow conditions of his existence."

Eleventh argument: Worse than ignorance. Morgenthau in '72 states: "Science gives to the threat emanating from politics a necessarily inadequate response, intellectually paradoxical and empirically an admission of failure. Thus, it appears preferable to know nothing at all to knowing something meaningless and useless and to counter a threat emanating from politics with a response likewise emanating from politics, that is, political action."

Twelfth argument: Breach of policy line. Morgenthau in '72 states: "The rise of science to power in the modern state will have to be paid for, at the very least, by a drastic impairment of its creativity. Its creative imagination will have been stifled by its commitment to the purposes of the state."

Thirteenth argument: Militarism. Morgenthau in '72 states: "The scientific expert, through his dynamic involvement in the political process, will become both the proponent and the ideologue of political and military policies."

Fourteenth argument: Science wars. Morgenthau in '72 states: "Advances spell more than a shift in the relative distribution of power. They may well mean the difference between victory and defeat, survival and utter destruction."

Fifteenth argument: No benefits now. They're all past. Morgenthau in '72 states: "While science elates man with the promise to transform homo faber,... it also depresses him. By the same token that it promises him the creation of new worlds, it threatens to destroy the only world he has known, and has already destroyed a significant part of it."

Sixteenth argument: Destroys man. Verschuur in '78 states: "It is possible that humans won't survive very long into the future in any case. Besides obvious terminators such as global nuclear holocausts, there is also the possibility that, as a species, we have already become too intelligent to survive much longer."

Seventeenth argument: Destroys solar system. Hubbard in '81 states: "Even though capitalism may have been necessary to develop science and technology, it cannot be allowed to use them to exhaust, and perhaps even destroy, the planet (and indeed the solar system, now that technological innovations have begun to pollute the moon and 'outer' space)."

Eighteenth argument: Science stops revolution. Hubbard in '71 states: "Science can help to destroy us (quite literally and in an assortment of ways), or it can help to liberate us. The ideological role of science and its power to mystify are enormous. To examine the sources of that ideological power and to understand it clearly are essential acts for anyone who wants to help bring about the needed radical transformations of our society." Gorz in 1976 states: "Any progress in knowledge, technology and power that produces a lasting divorce between the experts and the nonexperts must be considered bad. Knowledge, like all the rest, is of value only if it can be shared." Sweezy and Magdoff in 1980 states: "Along with the continued expansion of capitalism come the political destruction of the biosphere, the exhaustion of the earth's natural resources, and the ever growing threat of nuclear disaster. What we are now facing is quite literally a race between revolution to save the world and perpetuation of a bankrupt capitalism that will destroy the world."

**B. Next series of arguments: On tech.** The links were read above. Impacts:

Hall in 1981 states: "With each new technological breakthrough in a capitalist society the worker increasingly loses influence or control over the production process and over his relationship to that process."

Ernest Mandel in 1980 states: "We believe that science and conscious human endeavor can solve any problem that science, subjugated to the private profit motive, has created. But it is clear that in a capitalist economy such solutions will not be applied, at least not on sufficient scale to prevent a new phase of accelerated anarchistic economic growth that would increase the many threats to our common future."

Loften Stavrianos in 1976 states: "They [scientists] perceive this technology to be more a Pandora's box than an Aladdin's lamp. Like Saint Cyprian they foresee climatic catastrophe, soil and mineral depletion, and global famine. And to these familiar plagues they add modern horrors: unbreathable air, unpotable water, lifeless oceans, lakes and rivers, and overhead the Damocles sword of the hydrogen bomb."

**C. Next underview: Encirclement.**

Subpoint: Links. Wall Street Journal in 1984 states: "Laurent Fabius, France's minister of industry, told a new conference, 'The countries of tomorrow that don't have autonomy in space will be countries of the second rank.'" Campaign for Space Update in 1982 states: "Once we had a bold plan for the future with long-range policy goals. Today we are in danger of losing our leading edge to countries which recognize the importance of long-term space policy goals. It is clear that the development of a national policy is a critically important one for this Congress." Also from Martinez California News Gazette in 1984: "We're only going to be able to discover the nature of the universe once. It's like Columbus and Magellan. And the nation that does it will be in an unusual position of intellectual strength."

Subpoint: Impact. Stanley Hoffman in 1983 states: "Nothing could be more dangerous than giving the Soviet Union the sense of being cornered—surrounded by hostile forces masterminded by us."

**D. Next underview: Solar Powered Satellites (SPS).**

Subpoint: Link. Cosmic Search in January of '79 states: "Radio astronomers and SETI observers are concerned because all satellite transmitters are potential sources of interference of the worst kind since they can beam directly down into the super-sensitive earth-based telescopes. It is bad enough to have transmitters of 1, 10 or even 100 watts power on such satellites but the millions and billions of watts from a space power station could be devastating."

Subpoint: SPS could be achieved. Smith in '78 states: "SPS could be commercially available in the 1995-2000 time period." Stine in '81 states: "An SPS transportation system can be built starting today with technology that is either in existence or that can reasonably be expected to be in hand by 1990."

**E. Underviews: SETI contact is useless.**

Smart ETs won't talk to us anyway. Sagan in '73 states: "Civilization much advanced beyond us would in general not be interested in communicating." David Black in '77 states: "The history of the Earth advises us that interacting with another civilization will be no trivial problem. Members of one culture have often enough failed to appreciate the customs and beliefs of another, and this has led to unfortunate results. It is an observationally determined fact that men of equal intelligence but different cultural backgrounds may not understand one another." Montagu in 1973 states: "We still have about a dozen of these societies like the Andaman Islanders, the Kalahari bushmen, a few remnants of the Australian aborigines all of whom we are very busily engaged in destroying, but not learning anything from, because we approach them with attitudes which are so destructive."
MESSAGE;

        $this->createMessage($debate, $debate->negative_user_id, $message, 2, $baseTime, $messageIndex);
    }

    private function addAffirmativeSecondConstructive($debate, $baseTime, &$messageIndex)
    {
        $message = <<<'MESSAGE'
**Second Affirmative Constructive**

I think this round will be very clear.

**Topicality Responses:**

**1. Parameters Indicate Clarification**

His first argument is that parameters indicate clarification. My first response is that the affirmative case will be binding. The affirmative case will have to beat them; there's no one else's in the speech. Second, parameters have been abolished, and to that extent, they should not be a valid element in the round. Third, it only indicates a reasonable interpretation, and doesn't indicate anything more. Fourth, I think as long as we have a reasonable definition, that will take it out.

He then said you have to be in space. My first response here is that dictionary definitions of the word "of" are relevant. Black's Law Dictionary in 1968 defines "Of" as "Associated with or connected with, usually in some causal relation, efficient, material, formal, or final."

Listening for SETI is exploration of outer space, and this is a contextual definition, field context, and meets all the standards. Dyson in '82 states that one shouldn't distinguish between searching for extraterrestrial intelligence and general exploration of the universe; the two are really the same.

My third response, three subpoints here, is that SETI increases space-based research. The current strategy is to send probes out to identify who and where we are. We need to reserve the waterhole so that we can hear the response of the ETs. Thus, the plan enhances space-based exploration.

Fourth response, the plan clears radio interference which is in space, which enhances exploration to occur.

Fifth response, the negative definition is unreasonable. By their very nature, no exploration case could be topical, since all exploration necessarily requires Earth's synthesis of information to achieve any advantage.

Sixth response, the affirmative meets the negative definition. The plan increases exploration of outer space, since radio signals are in outer space.

Seventh, the affirmative meets the premise of the negative position. We get the same signal whether we listen on Earth or in space. Consequently, we are the equivalent of space-based listening.

Eighth subpoint, negative definitions are not inclusive. These definitions do not prove that our definitions are wrong. Rather, their definitions only prove that multiple definitions exist. This means our definition should be preferred, since we are affirmative and enjoy definitional presumption. Hence, we meet their criteria of field context and grammar.

Ninth subpoint, the affirmative satisfies the spirit of the resolution, indicating that all you have to do is traverse space only for something. That's clearly what we do.

Tenth response, the affirmative completes a research program. The affirmative augments existing space-based research by NASA of outer space. To that extent, the plan is part of a systematic program that goes far beyond the Earth and thus satisfies the resolution.

His first standard here indicates it has to be in space. My first point is that's not talking about exploration; it only indicates one example.

**2. Effects**

His next subpoint would be effects. My first response is that it seems to me there are direct effects. That's proven up above. Second response, he never indicates why effects is bad. So if that is a legitimate topicality argument, if he's going to advance this, he must indicate an impact. Third response is he never indicates how you determine what is effects and what is not. I think we clearly meet that.

**3. Interactive Element**

He says, interactive element. My first response is the topic indicates "and/or," which indicates you can do one or the other. We increase exploration. Second response is no new answers here. If he blows this up, I reserve the right to make new responses.

**4. Standards**

He says, standards. He says, A subpoint, reasonability is bad, because that assumes you have no context, no way to determine. Hence, as long as they set up grammar and field context, that's enough. Second response is all we have to do is be reasonable. Those decreases are granted if they force us to abide by their standards, because they could define us untopical and win every round.

He says, field context. I think my definitions up above beat that.

He says, grammar's key, but evidence I read up above indicates contextually and in grammar we are exploring space.

He says, all equals meaning. First subpoint here is that even if all words equal meaning, he never indicates how we decrease.

**5. Topicality is a Voting Issue**

He says, topicality is a voting issue. That's OK, but I think we're topical.

**Case Responses:**

**On the first (observation):** He indicates that they lie and the problem equals no coordination. My first response is there's no analysis as to why they would lie, and there's no evidence indicating they would do so. Second response here is if this is true, his sources are certainly just as incredible as ours, indicating they would all lie. Third response is evidence we read on the case side indicates the system is coordinated now. What increases are for extraterrestrials? That is never denied.

**Contention I: Inherency**

He says, decreasing right now. My first response is that's currently, in the status quo. Evidence we read in 1AC and all we extend with indicates it's increasing. New Scientist in '83 states: "A solely ground-based search is fast becoming unrealistic, given the possible blocking of alien signals by man-made interference both on Earth and from satellites hovering in orbit." LA Times in '83 states: "Within a decade it may be impossible to 'listen' to anything from the surface of the earth." Indicating it would be impossible.

He says, two subpoint, low power is compatible. So what? Evidence does not indicate they're doing this now or they're going to continue to do this in the future. It only indicates this might be compatible. Evidence I read to you indicates they will not be able to do it.

He says, next subpoint, waterhole is clear. My first response is that only talks about now. Evidence I read says within a decade it's going to be blocked.

He said, next subpoint, it's not blocked now. My first response is that's in the current system, but the evidence we read indicates it would be blocked in the long term. Second response is the evidence is not necessarily talking about the waterhole. It indicates for amateur astronomers, and does not indicate specific scientific research.

He says, next subpoint, non-unique. My first response is our evidence is specific evidence indicating it blocks it. This evidence is not specific. This won't take any of the waterhole advantages on that level.

**Contention II: Advantage**

He says, first response, (1) tech is bad. My first response is he never indicates how we increase tech, and that's going to be important on the link. He says, (2) scientists lie. My first response is he never indicates these scientists lie. That's a blatant assertion on his part. Why would these individuals lie? Second response is if you look at the quality of the evidence and the amount of evidence I've read, I'm certain you'll see that our evidence is just as good as theirs. Third response is negative evidence is bad too if that's the case. I mean, certainly there is no reason to assume that these people would be good.

I would argue, first subpoint here, and that is scientific benefits could be enormous. And I'll let you note that the evidence on scientific advantages is very specific to ETs, and all of theirs on the bottom is very general. OAST in '76 states: "The discovery of extraterrestrial civilizations would have enormous benefits that greatly exceed those of any other venture ever undertaken by the human race."

Second response is ETs could probably solve our terrestrial problems. 1AC Sagan, Healy, and Stull evidence proves. More support from Grey in '79: "Who can guess what might ensue? New technologies needed to feed the earth's mushrooming populations or to provide ever-increasing sources of energy; a different basis for the world's religions; new legacies of arts and letters; a change in our views of human and international conflicts; new approaches to education, the development of undiscovered branches of science; and on and on."

Three subpoint is that ETs could help us preserve our civilization. Kardashev in '78 states: "For the information obtained as a result of the discovery of intelligence in the cosmos may point the path to development of our civilizations over astronomically long periods of time."

Four, ET contact is vital for survival. 1AC evidence from Kraus proves. More evidence from Angeles in '76: "In order to achieve the necessary advance of his intelligence for survival man must gain information from communication with more advanced extra-terrestrial intelligences, from whom he can learn, or by whom he can be challenged to learn, or who can help provide some of the solutions to man's rapidly increasing and seemingly insurmountable problems preventing him from progressing to a higher state of intelligence." Which subsumes all their turnarounds at the bottom.

Five subpoint, SETI will provide answers to our other scientific questions. Crease in '84 states: "A surprisingly large part of the scientific community, eager to solve such mysteries as the nature of star formation, the origin of complex organic molecules, and the early course of life on Earth, considers SETI the only means to do so." Indicating, taking out all their problems about how they can't determine what the theories are now.

Sixth subpoint is detecting ETs will tell us we can survive as a species. Sagan in '79 states: "The existence of a single message from space will show that it is possible to live through technological adolescence..." Indicating the technological problems can be solved if you look at ETs.

(3) He indicates on the bottom stuff, that Healy and Stull evidence that it's all for the evidence it all indicates that it is true. He enters no counter-evidence.

(4) On the Kraus evidence, he says if explore would be bad, but no evidence ever indicates that. He says, don't know if they get the advantage, but the evidence that we read clearly indicates that we would know the advantage and would be able to determine. He said, third subpoint, like listening to the devil, but that's not true. Evidence we read indicates it will be good.

(5) On Sagan evidence, he indicates it's not true, but no counter-evidence. Never indicates no certainty there.

On Sagan evidence on the bottom, he indicates it is not true, but, no, certainly it's not. I mean, all the evidence clearly indicates that this was what would happen.

**Underviews:**

**A. Please go to technology.** This is all general evidence that does not apply to SETI. He says, subpoint, increase science. My first response is (1) they're doing SETI now. To that extent, it should already be occurring in the current system. They're already doing these other things. All we do is facilitate what they're already doing.

Second response is (2) they're already increasing probes. Halley, Galileo, Jupiter probes, etc. To that extent, science should already be increasing.

Third response is (3) there's no linearity of the impacts. He never indicates to you what threshold we cross to get the impacts. He never indicates that it will happen in the long term.

Fourth response is (4) other examples in the evidence indicates the status quo is already doing it now. Indicates even if you didn't do the plan, you would still get the increases.

Fifth subpoint, (5) no big tech spinoffs from SETI. Heeschen in '78 states: "The potential scientific by-products of the proposed searches are not particularly impressive..."

Sixth subpoint here, his (6) DAs are not unique. Space tech will be developed anyway for communications satellites. All the evidence on the disad indicates that. A subpoint here, is not unique. Tech will increase in the future. Finniston in '82 states: "The momentum of investment in science and engineering ensures continuing development of a technological bias in living conditions for some indeterminate, but long time to come." Indicating it's already going to increase.

Please go to his specific turnarounds. All this evidence is very general, and my evidence up above takes it out. He says, first, (1) it's bad. My first response is not specific. He never indicates what would occur. Second responses here is ET denies that. In the current system, they're already doing this. He never indicates any bad problems that are happening.

His next subpoint, (2) errors destroy. My first response is it means no disads to the advantage, because they're not going to take any changes in science. They're not going to do anything different. Second response is he never indicates this is happening. This should be happening in the study right now, and it's certainly not.

He says, next subpoint, (4) no benefits. My first response is that evidence indicates new tech, indicates according to the present system. Second response is after the evidence I read up above indicates we'd be able to solve with ET.

He said, next point, (5) infinite regression. My first response, that doesn't say it's bad. It only indicates more questions. Second response is ET can solve our problems. That would solve infinite regression.

He said, next sub-point, (6) ignores innovation. My first response is he never indicates it would be good. He never indicates new, good innovations are occurring. Second response is ET could solve to the extent that we increase the amount of innovation that occurs and get the good ones.

Group (7 and 8) seven and eight. Indicates it is bad, and it's lies. My first response is he never indicates there is anything bad that might occur because of this. Second response is not specific to ETs. He never indicates they'd lie on this level. Third response is no solvency evidence for this. He never indicates the status quo is going to solve these problems. I mean, this is just not unique to the affirmative.

He says ninth subpoint (9) immoral. My first response is this is due to bad science. ET solves that.

He says next subpoint (10) it hurts the individual. My first response is no impact to this. He never indicates how it takes it out. Second response is evidence we read on the case side indicates it would help the individual, increase cohesion, etc.

His eleventh subpoint, (11) worse than ignorance. That's not true. Case becomes disad on that level to the extent that if you don't know about ETs, it would be worse.

He says, (12) policy line. My first response is empirically denied. They've already acted in ETs. Second response is he never indicates any solvency to this. What is the status quo going to do that they're not doing now?

He says, (13) militarization. My first response here is no scenario. He never indicates what's going to happen. Second response is Ballistic Missile Defense (BMD) deters first strike. Payne and Gray in '84 state: "A defensive deterrent would thus present powerful disincentives against a Soviet nuclear first strike." Third response here is that BMD equals disarmament. Heflin in '84 states: "The incremental employment of a highly effective defense against offensive missiles will ultimately drive those missiles to extinction." Last response is decrease risk of war. Jensen in '84 states: "Active defense offers ... the only realistic possibility of reducing or eliminating the frightening specter of nuclear holocaust..."

He says next (14), equals science wars. My first response is no scenario. He never indicates what's going to happen. Second response should be happening now. No evidence indicates empirical impacts at all. Three subpoint is ET solves that, because it brings the world together, causes cohesion, and stops scientific competition.

He says, (15) no benefit now. My first response is that is talking about status quo technology which we can solve.

He says, next subpoint, (16) destroys man. My first response is that you need ET to solve. Case evidence takes it out.

He says, (17) destroy solar system. My first response is unclear. He never indicates how that would happen. Second response is that ET solves these to the extent that we eliminate the manipulation of the old technology.

He says, (18) stops revolution. My first response is no. We have evidence on the case side which indicates we can change mindset about technology. We can solve. Second response is status quo is not doing this now. He never indicates how it would happen under the current system. He never indicates revolution is coming.

**B.** He says, first subpoint on the next step, his next underview, link evidence indicates the impact of capitalism on society. My first response is (1) no evidence indicates tech would mean destruction. Second response is (2) he never indicates we get a unique increase. Evidence above on tech takes it out. Third response is (3) does not indicate we increase satellites, etc., and that is what all this evidence assumes. It assumes that we increase some absolute technology which we don't.

**C.** His first disad is encirclement. My first response is (1) no evidence indicates we increase space. Evidence is certainly not correlated on that level. Second response is (2) it seems we should get some sort of absolute increase in space. It seems that you get some sort of increased technology which would cause the perception. Third response (3) is not unique. Shuttle's going up now, and someone should already perceive that we're doing this. Fourth response (4) is not unique. Space shuttle's gone up. I mean all these other probes have gone up. They should be assuming that we're looking. Fifth response, (5) it assumes militarization. There is no evidence that ever indicates that we're ever going to get this in the current system. All the evidence indicates that it might be on the side.

Sixth subpoint here is no threshold. (6) He never indicates how much you have to increase this before it increases the perception, the semblance that this would occur.

Seventh subpoint here's (7) to a certain extent the impacts. Case evidence would solve it anyway.

**D.** Let's go to the next disad, SPS. My first response here is (1) no evidence ever indicates they're going to do it now. Second response is (2) they can use a different frequency. You don't have to ban it. Third response (3) is no proof they're going to use it in the long term. No one ever indicates they would ever do that. Fourth response is (4) no impact to this. He never indicates oil war, etc. I suggest new impacts would be illegitimate. Five subpoint is (5) ET solves. That is on the case side, indicates we can solve energy problems now. Sixth subpoint here is that (6) no evidence indicates they're going to have new satellites to go up, and that we understand would be taken out. Argue seventh subpoint here, and that is, (7) turnaround:

(a) Subpoint is SPS stops emitting CO2. New Jersey Bell Journal in '82 states: "The power satellite would open no carbon dioxide into the atmosphere and would produce no radioactivity."

(b) Subpoint here is that you have to have increased CO₂ to stop ice ages. Woodwell in 1980 states: "The CO2 content of the atmosphere in the recent glacial period was about half the current amount ..."

(c) Subpoint here is impact. Global starvation. Ponte in '76 states: "Perhaps two billion humans will starve to death, or will die of the symptoms of chronic malnutrition."

**E.** He says on the bottom, underview, in the case they would not solve. My first response is that (1) they would send signals. Marx in '79 states: "An expanding intelligent race might be interested in sending information through space and time to another planetary system and to a much later era." Two subpoint is (2) leakage signals could be detected. Bellingham and Pešek in '82 state: "A signal could be... a deliberate transmission specifically for the purpose of attracting the attention of an emerging civilization. Alternatively, it could be a 'leakage' signal not intended for detection by other civilizations."
MESSAGE;

        $this->createMessage($debate, $debate->affirmative_user_id, $message, 3, $baseTime, $messageIndex);
    }

    private function addNegativeSecondConstructive($debate, $baseTime, &$messageIndex)
    {
        $message = <<<'MESSAGE'
**A. Technology (Technology Disadvantage)**

This is an argument against the affirmative plan. We have two basic arguments against them in the round.

First, time-frame arguments. The SETI program will spin off science and technology that happen just from their search efforts, which they allow, and which will be under their plan. Scientists, for example, Ed reads cards in the first negative about radio astronomy stuff – things that exist independently of actually whether or not they find ET, or whether ET is out there, or whether we could decode any messages. We have this spin-off which happens sooner in the time-frame.

Second argument, encirclement will take out the links on the impacts that she claims, that they make everything nice.

Her first argument on tech was (1) the present system has lots of tech now. First, that's not true. Muller in '80 states: "Technology has reached a plateau the men in the white lab coats warn the world not to expect many more miracle products in the coming 10 years...." Eventually, no more tech. Rifkin in '80 states: "Eventually the technology bottoms out altogether as the energy environment it was made for nears its own entropic water-shed. In a recent cover story in Newsweek entitled 'Information,' the editors candidly acknowledged this basic reality: 'To some extent, of course, erosion in America's technological edge is inevitable. No longer can the U.S. count on the natural abundance of its frontiers the resources have been explored and sometimes depleted.'" Rifkin continues in '80: "Many of the experts on technology even suggested that diminishing returns might have set in across the board and that America's great technological strides of the past would probably never be repeated."

She argued, second, (2) they don't need it. They increase tech with the plan. First, for example, what to look for is enough. Black in '77 states: "It will be necessary to develop efficient techniques to identify weak signals embedded in noise, to distinguish signals of artificial origin from natural phenomena, and to evaluate whatever information they might contain. These require studies of pattern recognition at low signal-to-noise ratios, and studies of decoding strategies." Research is needed on this. Now, this is unique, because in the present system we don't get any signals. They're all totally crowded out, right? You just turn on the thing, and you get a bunch of white noise, and static, and stuff. They allow these signals to get through. People are going to have to develop all this tech software in order to understand and decode the signals. SETI, therefore, pushes tech a lot. Asimov in '79 states: "The very attempt to construct the equipment for Project Cyclops will succeed in teaching us a great deal about radio telescopy and will undoubtedly advance the state of the art greatly even before so much as a single observation of the heavens is made." They don't just—I mean, this is just on point to one example, but, of course, it would do it.

Third argument, (3) if they get any tech from aliens, any message, that would be disastrous. Dyson in '64 states: "Intelligence may be a cancer of purposeless technological exploitation, sweeping across a galaxy as irresistibly as it has swept across our own planet. In this connection it is of importance that, even at the slow rate of interstellar travel that is unquestionably feasible, the technological center could spread over a whole galaxy in a few million years, a time very short compared with the life of a planet." Palour in '82 states: "If we encountered aliens, they'd be our technological superiors by several million years. Is it conceivable that a race so advanced could behave like extraterrestrial Nazis? The answer, I'm afraid, is yes. Technological excellence does not imply moral excellence. Nothing in our history is plainer or more tragic, than the gulf between cleverness and wisdom."

Her next argument is that (5) they don't increase tech very much. First, it's linear. Rifkin notes in '80: "The world is becoming more disordered because each time we apply a new and more complex technological solution to a problem, it's like dousing a fire with gasoline. The faster we multiply the 'transformers,' the faster the available energy is used up, the faster the dissipation and disorder that result. The problems proliferate faster than the solutions."

Second, greater disadvantage, on outweighs. Rifkin notes in '80: "The faster they run, the further they fall behind. Each quasi-solution has a multiplier effect on the residue of the problems." He continues, "Since human survival depends upon available energy, this must mean that human life is always becoming harder and harder to sustain. More complex technologies must be devised at each stage of history just to maintain a moderate level of human existence." Feinberg in '77 states: "We should generally realize that most of the identified ill effects that humanity has had on the environment were neither expected nor intended, but have occurred because no one knew what the results of various actions would be. For example, it was not known in advance of the development of internal combustion engines how much sulfur dioxide, carbon monoxide, and other pollutants would build up...." That proves they can't control it.

Now, she says, (6) they don't increase. That's taken out above. She says, no increase satellites. First is that she never reads any cards indicating that space technology is increasing. Below on encirclement, we will read evidence that proves this is unique. Second argument is this justifies counterplans, and we will counterplan to take out any argument, any current space development program that they can indicate that makes these disadvantages non-unique. It's net benefit competitive, because it would eliminate the need. In other words, it would be better to do the counterplan alone and have neither—whatever technological benefit they can isolate, and nothing else—than to have the affirmative plus no technological development, because that would increase tech somewhat, and that would cause some harm.

**B.** Now, she drops all the impact cards. Say it increased capitalism, and that it the Mandel card says, if you are in the system now, then it's never used productively. It's a terrible thing.

She drops the Stavrianos card that says, you know, climate, minerals depletion, soil, unbreathable air, you know. Nuclear war. All that evidence is conceded. We win that pretty clearly.

**C. On encirclement,** her first argument is (1) doesn't deal with space. Our first answer is that technology increases a lot, and that increases encirclement. Well, more evidence comes from the evidence here comes from Hall in '81: "With each new technological breakthrough in a capitalist society, the worker increasingly loses influence or control over the production process and over his relationship to that process."

Next argument, that knowledge is important, is very important to international prestige. Planetary Report notes in '82: "Societies undertake such great works of science and exploration for many reasons beyond the gathering of new knowledge. By taking on great and visible works of technology, in a world where broad technological capability is the most significant source of economic well-being and power, we demonstrate and solidify our position of world leadership." Science Digest notes in '81: "The exploration of the Solar System is a great imagemaker; it is a highly effective way of conveying the technological might of a nation. The Russians sense this, just as they sensed the potentially explosive impact of the first Sputnik on world opinion." Morrison notes in '81: "In the same way, the success of our space missions contributes to American security by increasing the confidence of the world in our economy and the capability of our advanced weapons system. In a world where the ultimate weapons must remain forever untried, much advantage is to be gained by a demonstration of capability...." It advertises U.S. strength. As Anderson notes in '83: As Lawrence Lessing wrote: Knowledge, more than guns and butter, is the true power of modern states. The nation that disdains non-essential knowledge will eventually find that its information base is too small to sustain its future. I mean, imagine what would happen if we, the United States, decoded a SETI message tomorrow, before anyone else in the world had. We'd say, gosh, look at us. We're amazing. We've got the story of all history, and the Russians don't have it. You know what I mean, there'd be a tremendous—it would be like, you know, Sputnik a hundred times over. In fact an on-point link to SETI. You would be used by the military, and it would be used in this way in order to compete with the Soviet Union. As Wald notes in '73: "Once again, all the nations will be listening in equally, provided they have equally big radio telescopes. So we will have a radio-telescope race, and God help the nation that has a somewhat smaller radio telescope than the others. As for the community of world science, this is the first time I have heard that it covers weapons technology." Science Digest notes—no, no more evidence from Olson in '84: "In the past the United States was able to regain the lead because we were the world's economic leader, exuberant and resource-rich. Today our leadership is no longer so pronounced, and we cannot rely on regaining a lead if it is lost. We can't play catch-up ball any more; the other players have learned the game, and they're as good as we are."

She says, second, (2) assumes absolute increase in technology. First, it's knowledge, not technology. That's the prestige that comes from being on the forefront of the SETI program which they independently say, the United States can't do now, and that's what he says in cross-examination. Second, it's unique. Astronautics and Aeronautics in '81 states: "The U.S. has been following the first path with grand missions operating at the technological and scientific frontier, garnering major 'firsts, but, in a time of constrained resources, coming more and more infrequently. World leadership in deep space is defined by this class of mission, and up to now it has clearly belonged to us. There is a time dimension, however, to leadership. Even an elaborate and successful mission loses value if it occurs too late. Thus this slowing of the pace will be hazardous." As Covant notes in '84: "Is the United States about to relive the industrial debacles of its automobile, steel, and electronic industries, and now even its once unequaled aircraft industry? America is still desperately hanging on as one of the world leaders in space, but, with present federal policies entrenched, it won't be long before that industry too will fall behind as the others did before it."

She says, (3) not unique. Should be the shuttle. There's no evidence right here. We indicate that we're falling behind now, and that's enough.

She says, other probes. There's no evidence read for how these, why these would increase. Also, this justifies a ban on them, and we would extend the counterplan to that too.

She says, (5) assumes militarization of space. No, the Soviet Union sees space as an ideological battleground, and the impact occurs on Earth. Deudney in '82 states: "Clearly, Soviet leaders have consistently placed a high priority on space activities, linking this exploration to the most important accomplishments and destinies of socialism." Morgan in '83: The Soviet Union is paranoid. They already perceive themselves as being closed in on all sides. Unfortunately, U.S. economic expansion into outer space serves only to heighten their paranoia. The impact evidence is given from Brzezinski in '82: "Sharper conflict could even eventually produce an encirclement of the Soviet Union, spanning America, Western Europe, China, and Japan—thereby bringing about precisely the same consequences that the expansive Imperial Germany of the pre-1914 years brought upon itself, even though its policymakers too feared just such an encirclement." Bailer and Afferica in '83 state: "... American foreign policy is nothing other than an instrument for creating the best possible conditions for inevitable war between East and West."

She says, (6) no threshold. There's this huge risk. She drops the California Gazette card, the nation that discovers the secrets of the universe will be incredibly strong. The Hoffman card says nothing could be more dangerous. There's a huge risk here, and the threshold's very low.

She has no time for, very soon, because they got a lot of spin-off from knowledge and tech in the interim.

She says, case solvency, but this stops case solvency. It stops conscious exchange, because it would happen much more, before anything can happen on the case side.

**D. Now, on SPS,** she says, (1) not now. Yes, could happen now. She dropped the Smith '78 card which said, we could do SPS now.

She has (2) no frequency. First, those cosmic search cards said that this is an absolute barrier to the case, that you could never have any SETI at all, because the SPS will simply drown out all the signals. Their case evidence in the second argument is, their case says, they'll exclude things that infringe on this frequency, and since the SPS card says, SETI must be on that frequency, and it must be in the waterhole, therefore, they would eliminate. She said, they could shift, but there's no evidence, any evidence, that says, they could broadcast on another frequency, and they would be OK. She says, they don't make it, but they do impact. They do de facto. She says, (4) no impact. Wallechinsky, Wallace, and Wallace in '81 state: "If the development of space power has not begun [by the year 2000], within 10 years conflicts over dwindling fossil fuel reserves will precipitate war between the U.S. and the U.S.R. Escalation to full-scale nuclear war will occur rapidly."

She says, (5) case take out, but this will happen fast.

She says, (6) new satellites, but they stop that.

She says, (7) SPS leads to cooling. First, we're warming now. Cahill notes in '83: "Experiments run on computer models of the earth's climate suggest it will warm up at least one degree centigrade by the year 2000...."

She says, the convincing evidence goes for this. Bernard in '80 states: "Scientists are speaking clearly to us, and in unison. True, they disagree as to the timing and ultimate consequences of a warming earth, but they are in virtually unanimous agreement when it comes to defining the trend: warmer." No more cooling. Gribben notes in '82: "'The common misconception that the world is cooling is based on Northern Hemisphere experience in 1970.' Since 1970, the Northern Hemisphere has once again begun to warm up." Generally, this is true. U. S. News in '83 states: "Global temperatures will climb an average of 3½ degrees Fahrenheit by 2040 and as much as 9 degrees by 2100. Never in recorded history has the earth experienced such a rapid warming."

It's underestimated. It could be very big. New York Times in '83 states: "The [EPA] report, written by Stephen Seidel and Dale Keyes, was reviewed by about 100 scientists before publication and most of the criticism was that the projections of the amount of warming were 'too conservative.'" Also, warming is bad. It decreases agriculture. Gribben notes in '82: If summers are both wetter and cooler, the benefits of increased moisture outweigh the losses. One percent rise in temperature produces eleven percent decrease in yield worldwide.

This is on balance bad. Sinha in '82 states: "When a change in climate reduces the number of rainy days, there are almost always adverse effects on grain development even though biomass production may increase." This is as bad as nuclear war. The impact, Lockwood in '79 states: "If one examines predicted disasters—ozone depletion, catastrophic reactor accident, perhaps even nuclear war—one must admit that the serious climatic shifts caused by CO2 may be at least as likely as these, and more likely than some of them." Runaway greenhouse. End all life as Boston Globe notes in '83: Warmer temperatures would force more water vapor in the air. 900 degrees at the surface of Venus—like atmosphere, all the gases and liquids have evaporated and float in the dense atmosphere.

Cooling is not bad. First, won't happen. Warming evidence is overwhelming for us. Lockwood in '79 states: "Flohn (1977) suggests that the possibility of the occurrence of such a glaciation during the next century is of the order of 0.1-1.0 per cent. In contrast he estimates the probability of a transition to a warm period to exceed 10 per cent and possibly reaching 50 per cent." Second, cooling is too slow. Bernard notes in '80: "An ice age will not be much of a problem for about another 10,000 or 20,000 years, however. If our planet is still around then, we should have plenty of time to prepare." Third, that is, no snowblitz. Mitchell in '83: The world with snowblitz is not possible. Further, no impact. Just like winter. New Scientist in '84 states: "A full ice age may literally be no more than a severe, prolonged winter, like the worst winters we experience today."

**F. Underviews.**

BMD's bad. First, (1) cause plutonium shroud. New York Times in '84 states: "The 'star wars' defense is not only uncertain and expensive, but if it actually worked, atmospheric winds would deposit the plutonium in so wide a layer that it is difficult to see how life could be sustained."

Second, (2) depletes the ozone layer. Gribben in '79 states: If such ABM were defense, it would ensure very great depletion of the ozone layer, the mythical 'Doomsday machine' would collapse civilization as we know it. Also, (3) would cause first strike. Institute for Space and Security in '83 states: Warning time would be non-existent. The incentive to go first would be so strong that the onset of war would be inevitable and immediate. Daily Progress in '84 states: "Shielding this country from retaliation after a first strike by us is the only conceivable type of military use for the type of system they're designing right now...."
MESSAGE;

        $this->createMessage($debate, $debate->negative_user_id, $message, 4, $baseTime, $messageIndex);
    }

    private function addNegativeFirstRebuttal($debate, $baseTime, &$messageIndex)
    {
        $message = <<<'MESSAGE'
**A. Technology (Technology Disadvantage)**

I think I'm beating them on this issue. My first argument on top of the case is that no one else can be trusted. First of all, I read cards. (1) Subpoint is fraud. I give an example. Also, Broad and Wade in '82 state: "Every major case of fraud that becomes public is the representative of some 100,000 others, major and minor combined, that lie concealed in the marshy wastes of the scientific literature." (2) Subpoint is misprints. Even if they get correct scientific information, it's going to be misprinted. That's down below.

Her second argument, my sources lie. First of all, they're not scientists. They're revolutionaries, and they're social scientists. Second of all, scientists lie. Wilson in 1970 states: "Most of the arguments we hear in defense of leaving science alone is special pleading by a hierarchy that has been left alone in secrecy too long. It is only natural that scientists should not wish to give up this power. That some scientists claim that to direct science would win it merely illustrates their blindness to the subtle forms of direction already operating. I suggest that the processes of decision in science should be made open."

Next argument, Marxists are most qualified to judge science. Hubbard in 1981 states: "Marxists are in a particularly favorable position to engage in searching analyses of what is wrong with the structure of the scientific enterprise as a social institution...."

Next argument, and that is defense obscures the role. This is a per se voting issue. Rose in '84 states: "To speak of 'science for science's sake' is to mystify what science is and what scientists do."

Next argument is the most important issue in the round. Roszak in '72 states: "Science is not, in my view, merely another subject for discussion. It is the subject. It is the prime expression of the west's cultural uniqueness, the secret of our extraordinary dynamism, the keystone of technocratic politics, the curse and gift we bring to history." This is the topic.

The next argument is system coordination is near. First argument is it's assertion. They don't read a card. Second of all, it's not a natural result. Third argument, I say that science will lead to U.S. oppression which will independently take this out.

**II. On Case, Precludes.**

I'm going to grant the idea that we have all these things blocked up. Second contention, advantage. Group her evidence together. First off, my sources are better. Second of all, it's not empirical. Her argument is speculative. My arguments are empirical. Science has wrecked this in the past. Third argument, it is questionable which is dropped down below. Fourth argument, gives tech link. That's the sixth argument she said that's independent. Fifth of all, it's better. Their evidence says, the selfish reason as to why this is true. Marx has won a revolution for all mankind. Sixth argument is empirically disproven, right? The card of the evidence. We abuse them. Seventh of all, they might agree, because they think science is good, and I don't. Eighth argument is that technology is bad. In other words, I could agree with what they're saying, but I would suggest that my argument supersedes this, because it says, once we've got this information, then they'll wreck the society with it.

(4) On Kraus '82 card, the even-if-extinct card, I think my analysis is really good here. If they're extinct and it takes us this long to get there, then it would be really bad. I give an example from Cosmic Search in January '79: "Professor Josef Shklovsky of the Soviet Academy of Sciences believes that the nearest extraterrestrial civilization is probably at least 10,000 light years distant." Which means when we get the information, they'll be extinct anyway, and they'll have been destroyed because of tech.

(5 and 6) On the argument about Sagan's interpretation. First of all, there's nothing in the laws of nature that says that's why. I think this card is ridiculous. Second argument, assumes that we're able to speak with them. Sagan in '73 states: "In fact, there is almost certainly no civilization in the galaxy dumber than us that we can talk to. We are the dumbest communicative civilization in the galaxy. We are very much the low man on the exploitation totem pole." Now, nobody would want to talk to us. We're reprehensible.

**A. On the links to science, okay?**

First argument, (1) SETI now. First of all, you need to counterplan it out. Second of all, each step is important. I read a Gorz card which says, every time it's important. Third of all, you need for revolution. Dropped fourth argument, it's a decision rule. That's up above.

(2) On probes increasing. First of all, who cares? It's independently unique. She drops this. Second of all, they don't lower it much. Third of all, you can counterplan out the others. (3) On no linearity, this is blatantly wrong. On the fourth argument, (4) other examples given. Five, each step is important. On the fifth argument, (5) no technological spin-offs. Call for the card after the round despite the NDT rules. It simply doesn't say that is good. I read the preponderance evidence below, says every time we think it's good, they'll simply mystification. We're exploiting people, and we're leaving it to wars, and we're destroying the environment. We're going to destroy mankind and the whole solar system. This is our only press down below.

(6) On non-unique disadvantage. First of all, every step important. An increase in future. Save. All the arguments above apply.

**A1. On fraudulent.**

First of all, this means that she says, ET denies, but that's simply not true. I read cards saying that every time you have 10,000 examples and there are more, and also they destroy the validity. That's the second card that I read.

Also, I want to get on (4) no benefit. She says, no tech. But first of all, it's only good tech is past. That's an example of SPS. Second, any future will lead us down the road to destruction. An infinite regression. This means that more knowledge will not be useful, because we'll simply learn things nobody cares about like toast and jelly and things like that.

(6) On ignores real innovation. There's no real innovation, and that doesn't take it out. It means that no one will actually get any. It's a myth. She says, Why? Not specific. But I defend this up above.

(8) Hypocrisy and more. She says, what's the importance? First of all, I think we shouldn't be immoral. Second argument, at least, it's slavery. Journal of Chemical Education in '84 states: If the educated layman does not have the knowledge and understanding necessary to participate in decision-making—political decisions which affect them—is known as slavery.

(10) The next argument, destroys individual autonomy is dropped. She says, the impact? But I give the impact. Worse than ignorance. That's a clear argument. This is the only comparative analysis in the round. It says that it's worse to not know about the damned science, because it's bad.

(12) On policy line, means they can't repair. Which means that they're going to actually lead down the road to destruction. They won't reform civilization like Sagan and Asimov, and people like that have claimed up above.

(13) On militarism. No scenario. John takes out the BMD. They ask what's the problem with wars, but what are we solving on case side? If this isn't true, we're solving war. If you ignore the scientific benefits, and vote against the affirmative. On science wars, no scenario. Come on. I give you the example of nukes, there's chemical weapons, there's all of these bad things which have led to wars which have destroyed hundreds of millions of people—not that many, jeez, that's exaggeration.

(15) On no benefits, it's exaggerated. Morgenthau is not a scientist. (16) Destroy man. That's dropped. (17) Solar system. How? They're going to exploit. They're going to use this scientific knowledge. Go to the solar system.

(18) Revolution. OK? Each step is important. We're on the brink now. These cards are read. Sweezy/Magdoff. Destruction of man. You know, if you have any doubts about the significance of the case, it's very clear from science that each step is important.
MESSAGE;

        $this->createMessage($debate, $debate->negative_user_id, $message, 5, $baseTime, $messageIndex);
    }

    private function addAffirmativeFirstRebuttal($debate, $baseTime, &$messageIndex)
    {
        $message = <<<'MESSAGE'
**A. Case**

Group his first four answers. My first argument is none of this evidence applies to SETI. Second argument is this doesn't mean that all of their people don't lie, like the Marxists themselves. Third argument is it doesn't mean that all scientists lie. That is too broad; some scientists lie. It does not apply specifically to our scientists. No reason why they would.

He says, next, Marx is most qualified and most important issue. First, doesn't assume specialist or the specific evidence of the round. He has no specific evidence that takes this out.

He says, next, U.S. would repress this evidence. First argument is, no, the U.S. would increase cohesion. That's the evidence from the 1AC. Second argument is there is coordination now, and that's the 1AC evidence.

He attacks number two on the advantage. He says, sources are better on the empirical evidence. This is not true. Our evidence is just as good as theirs.

On the evidence. All I want to say here is that it's not true. Our evidence takes this out independently.

He says, tech link, but that is not true. His evidence assumes that we spin some link, not that we get ET's tech, and that is not the same type of tech. He says, better evidence, but that is not true. He says, empirically disproven. This is not true. He's assuming that all tech is going to increase and it's going to be bad. It is not up to me ET tech, which we have never had, and we argue it's going to be good, and everybody argues good. He says, tech is bad, and his argument supersedes, but that is not true. He assumes we are going to build a new tech of our own, not that we get ET's tech, and he drops all our specific responses. We're arguing that this flips all the tech underviews independently. Gives us a new kind of tech, and those are beneficial. I want no new responses in the next rebuttal.

**II. 4.** On the evidence, he argues on the Kraus evidence, he says, very good, but that's not true. It's irrelevant, because we indicate that we'll be able to get it off echo even if it takes a long time to get here, because they're so much more advanced.

He says, our evidence does not say that, but that is not true. Evidence in second said we'd be able to decode, and he didn't read any second evidence anyway.

Okay. He grants us the case, indicating we could solve.

**A. First overview on tech link, I guess (1).** He says, counterplan beats. I'll beat counterplan there.

(2) On probes. He says, who cares? Doesn't apply. First argument is, it is not unique. This evidence indicates it's sending out probes. It should be increasing tech. Second argument is, we are increasing in the status quo, right now, so there's no need for the DA, because we're increasing SETI technology. It's just that it doesn't work very well. And they're increasing their tech, and that's the whole link anyway.

(3) He says, next, it's wrong, because not linear, but that's not true. A war cannot be linear. No way he can get that.

(4) He says each step is important, but if the status quo is doing it, there's not going to be any significant risk in the plan, and how much are we going to increase it.

(5) Off number five, he says that he doesn't think tech is good. This evidence indicates there is no spin from SETI specifically, indicating it takes out the entire links disadvantage, and he has no responses on that.

**A.** He says, next, is not true, but it is true. No evidence indicates that. On the science increases itself. He drops the first argument that says, all this evidence is general, and the specific SETI evidence turns this, and that will take out this whole thing independently even if I lose everything else.

Now, on the—he argues, help the first one that—It destroyed man's autonomy. It doesn't apply. Second argument, the evidence doesn't indicate the man would be that would be so bad. Third argument is it's beneficial. Broad in 1984 enables millions to remain alive, indicating it would be good.

(3 and 4) He argues on the three and four subpoint, it only applies to new tech. First argument is that is not true. Evidence up above indicates it's been good. Second argument is doesn't apply, because empirically tech has not been so bad. Third argument is that ET's tech is different, doesn't apply.

(5) On five, even if Russia needs there's no used knowledge. First argument is, if that is true, tech is not true, because you wouldn't use knowledge to get increased tech. Second argument is that is irrelevant anyway, because—just more questions. There's no impact to this.

(6) On six, ignore no real innovation. The first argument is there's no impact in that. So what, you don't get any more innovation. He doesn't give any impact. Second argument is that is not true anyway. He indicates the link would be—that all tech in the status quo is increasing.

(7 and 8) On seven and eight, he just says, shouldn't be immoral and slavery. There's no evidence indicates this bad. Second argument is, so what? We're going to solve all these problems with ET, indicating they're going to take away all man's problems, and that is specific evidence.

(9) On number nine, immoral. He just says it would be bad, but what is going to be wrong. What is going to be wrong? So what? Second argument is ET is going to solve this problem, and he doesn't apply to the affirmative anyway.

(10) On number ten, he says, she drops it. I say, no, there's no impact on destroying individual autonomy. What it's going to do? Certainly ETs can outweigh autonomy anyway.

(11) He says, next, a drug. We should—a drug. I drop it, but again, no, case is a disadvantage, indicates this turns this.

(12) On policy line, he says, no, we can never repair the system. That is not true, because ET is going to repair the system, and he has no response to that.

(13) Militarization. He says, short term doesn't solve, but again, that's dealt with the counterplan. Second response here is does not occur before 2001. No evidence ever indicates it does. He says, no benefits now. The case evidence is dropped.

**B.** Please go to the next subpoint on technology. He says, no more increase in decoding technology. Increases technology. First response is they're all going to be doing this now. No evidence indicates we get a net increase. Second response is evidence I read postdates his, indicating we increase technology now. He says, not true. He indicates that it is increased technology. We do not increase development now. First response is, again, my evidence postdates. Second response is not specific to SETI. No spin-offs. Third response here is no evidence indicates takes it out all.

Now, on the bottom, on counterplan not SPS. He says, exempt. First response is evidence indicates it could cause the problem. Thus, it now takes out the disadvantage. Second response is we'd be able to solve to the extent that we can decrease the amount of technology that would occur.

**C.** On the disadvantage on encirclement. If we win that the counterplan is not competitive then disadvantages irrelevant, because there would be no increase in space technology. The only tech expense would be on Earth, and hence on Soviet perception. No technology will be deployed. He says, pull over this increased tech, but, again, that is taken out on tech. He says, short term turnaround, but, no, not with the counterplan. He says, Soviet sees. I deal. That's not true.

**D.** On SPS. First response is no evidence indicates the status quo will put up the SPS: There's nothing here that indicates they're going to do it. Second response is plan is very clear. We can ask them to shift frequency, and we don't have to ban. Now, as long as the counterplan is not true, and there's no evidence on SPS in the short or long term, I think we can certainly win this.
MESSAGE;

        $this->createMessage($debate, $debate->affirmative_user_id, $message, 6, $baseTime, $messageIndex);
    }

    private function addNegativeSecondRebuttal($debate, $baseTime, &$messageIndex)
    {
        $message = <<<'MESSAGE'
**Second Negative Rebuttal**

**A. Technology (Technology Disadvantage)**

I think this is the most important issue in the round. I think I'm winning this issue. Let me tell you why.

First of all, she says, doesn't apply to SETI. But that's not true. I read evidence in the 2NC that says, SETI increases technology. Black in '77 states: "It will be necessary to develop efficient techniques to identify weak signals embedded in noise, to distinguish signals of artificial origin from natural phenomena, and to evaluate whatever information they might contain. These require studies of pattern recognition at low signal-to-noise ratios, and studies of decoding strategies." This is unique technology that they have to develop. Asimov in '79 states: "The very attempt to construct the equipment for Project Cyclops will succeed in teaching us a great deal about radio telescopy and will undoubtedly advance the state of the art greatly even before so much as a single observation of the heavens is made." This is unique to the plan.

Second argument, she says, Marxists lie too. But that's not true. First of all, they're not scientists. They're social scientists. They're revolutionaries. They have no incentive to lie. Second of all, they're the most qualified to judge science. Hubbard in '81 states: "Marxists are in a particularly favorable position to engage in searching analyses of what is wrong with the structure of the scientific enterprise as a social institution...."

Third argument, she says, not all scientists lie. But that's not true. Broad and Wade in '82 state: "Every major case of fraud that becomes public is the representative of some 100,000 others, major and minor combined, that lie concealed in the marshy wastes of the scientific literature." This means that fraud is pervasive throughout the scientific community.

Fourth argument, she says, no reason why SETI scientists would lie. But that's not true. Scientists have an incentive to mystify their work. Hubbard in '81 states: "The elitism of science enhances its mystifying power. The scientific elite not only helps to generate the mystification, it also stands to gain the most from it and therefore is its most willing victim."

Fifth argument, she says, our evidence is just as good. But that's not true. My evidence is empirical. Her evidence is speculative. Science has wrecked things in the past. Nuclear weapons, chemical weapons, environmental destruction. Her evidence just says, maybe ETs will help us.

Sixth argument, she says, ET tech is different. But that's not true. Technology is technology. Once you have the knowledge, you use it to build things. And those things can be used for good or evil. Dyson in '64 states: "Intelligence may be a cancer of purposeless technological exploitation, sweeping across a galaxy as irresistibly as it has swept across our own planet."

Seventh argument, she says, status quo is doing SETI now. But that's not true. The status quo has interference. The plan clears the interference, which allows them to get signals, which allows them to develop the technology I'm talking about.

Eighth argument, she says, no spin-offs from SETI. But I read evidence that says there are spin-offs. And even if there weren't, the plan still increases technology by clearing the interference and allowing them to develop decoding technology.

Ninth argument, she says, ET will solve all our problems. But that's not true. First of all, we might not be able to decode the message. Second of all, even if we can decode it, the technology might be harmful. Third of all, the very process of developing the technology to listen for ETs is harmful.

Tenth argument, she says, no impact to destroying individual autonomy. But that's a huge impact. Morgenthau in '72 states: "Science has already destroyed that realm of inner freedom through which the individual could experience his autonomy by controlling, however precariously, the narrow conditions of his existence."

**Time Frame**

This is crucial. The technology disadvantage happens immediately. As soon as they clear the interference, scientists start developing new technology. The case benefits, if they happen at all, happen much later. We might not get a signal for decades or centuries. And even if we do get a signal, we might not be able to decode it.

**Magnitude**

The technology disadvantage affects everyone on Earth. It leads to environmental destruction, nuclear war, and the end of civilization. The case benefits, even if they happen, only affect a small number of scientists.

**Probability**

The technology disadvantage is certain. Science always leads to harmful technology. The case benefits are speculative. We don't know if there are any ETs out there. We don't know if they're sending signals. We don't know if we can decode their signals.

Therefore, you should vote negative on the technology disadvantage.
MESSAGE;

        $this->createMessage($debate, $debate->negative_user_id, $message, 7, $baseTime, $messageIndex);
    }

    private function addAffirmativeSecondRebuttal($debate, $baseTime, &$messageIndex)
    {
        $message = <<<'MESSAGE'
I think this round is very clear. Let me tell you why you should vote affirmative.

**I. Case Outweighs**

First, the case outweighs all their disadvantages. The benefits of contacting extraterrestrial intelligence are enormous. We could solve all of humanity's problems - war, famine, disease, environmental destruction. The evidence from Sagan, Healy, and Stull proves this. These are not speculative benefits - they are the logical result of contacting a civilization millions of years more advanced than us.

Second, the case is certain to solve. The plan reserves the waterhole frequency band for SETI. This eliminates radio frequency interference, which is the only thing preventing us from detecting ET signals. Once we clear the interference, detection is virtually certain within a few decades.

Third, the case benefits affect all of humanity. Every person on Earth would benefit from the knowledge we gain from extraterrestrial contact. This is not just about a few scientists - this is about the survival and advancement of our entire species.

**II. Technology Disadvantage Fails**

First, no link. The negative never proves that SETI increases harmful technology. Their evidence is about general science and technology, not specifically about SETI. Our evidence from Heeschen proves that SETI has no significant technological spin-offs.

Second, no uniqueness. Technology is increasing in the status quo anyway. The negative's own evidence proves this. If technology is so harmful, it's already happening without the plan.

Third, ET technology is beneficial. Unlike human technology, which may be misused, ET technology comes from a civilization that has survived its technological adolescence. They wouldn't send us harmful information - they would send us solutions to our problems.

Fourth, time frame. The technology disadvantage, even if it exists, happens slowly over time. The case benefits happen immediately once we detect a signal. We could solve all our problems before any harmful technology develops.

Fifth, the negative's evidence is biased. Their sources are Marxist revolutionaries who have an ideological opposition to science and technology. Our sources are respected scientists who actually work in the field.

**III. Encirclement Disadvantage Fails**

First, no link. Reserving radio frequencies doesn't increase space exploration in any way that would threaten the Soviet Union. This is purely a listening program, not a space-based program.

Second, no uniqueness. The U.S. is already engaged in space exploration that could provoke Soviet paranoia. The shuttle program, space probes, and military satellites are all more threatening than a passive listening program.

Third, case solves. Contact with extraterrestrial intelligence would bring all of humanity together. It would end the Cold War by showing us that we're all part of the same human family in a vast universe.

**IV. SPS Disadvantage Fails**

First, no evidence that SPS will be built in the status quo. The negative provides no proof that solar power satellites are actually being developed or deployed.

Second, plan doesn't ban SPS. The plan only reserves frequencies for SETI. SPS could use different frequencies or be designed to avoid interference.

Third, SPS is beneficial anyway. Solar power satellites would provide clean energy and help solve the greenhouse effect. The negative's cooling arguments are outdated - the scientific consensus is that we're facing global warming, not cooling.

**V. Science Criticism Fails**

First, not specific to SETI. The negative's arguments about scientific fraud and deception don't apply to SETI research, which is simply listening for signals.

Second, empirically denied. Science has provided enormous benefits to humanity - medicine, communication, transportation, agriculture. The negative cherry-picks negative examples while ignoring the overwhelming positive evidence.

Third, ET contact would improve science. Contact with an advanced civilization would give us new perspectives on scientific methodology and help us avoid the mistakes the negative identifies.

**VI. Conclusion**

The choice in this round is clear. On one side, we have the certain benefits of contacting extraterrestrial intelligence - solutions to all of humanity's problems, scientific advancement, and species survival. On the other side, we have speculative disadvantages based on biased sources and flawed reasoning.

The plan is simple and effective. Reserve the waterhole frequency band for SETI. This eliminates interference and allows us to detect ET signals. The benefits are enormous and certain. The risks are minimal and speculative.

Vote affirmative. Give humanity the chance to join the galactic community and solve our problems with the help of advanced civilizations. The future of our species may depend on it.
MESSAGE;

        $this->createMessage($debate, $debate->affirmative_user_id, $message, 8, $baseTime, $messageIndex);
    }

    /**
     * メッセージ作成のヘルパーメソッド
     */
    private function createMessage($debate, $userId, $message, $turn, $baseTime, &$messageIndex)
    {
        DebateMessage::create([
            'debate_id' => $debate->id,
            'user_id' => $userId,
            'message' => $message,
            'turn' => $turn,
            'created_at' => $baseTime->addMinutes($messageIndex * 1),
            'updated_at' => $baseTime->addMinutes($messageIndex * 1),
        ]);
        $messageIndex++;
    }

    /**
     * ディベート評価を作成
     */
    private function createDebateEvaluation($debate)
    {
        DebateEvaluation::create([
            'debate_id' => $debate->id,
            'winner' => 'negative',
            'analysis' => <<<ANALYSIS
This debate centers on the resolution: 'Should the United States Government Significantly Increase Exploration and/or Development of Space Beyond the Earth's Mesosphere?' The affirmative proposes increasing the search for extraterrestrial intelligence (SETI) by reserving a specific radio frequency band (the 'waterhole') to eliminate radio frequency interference (RFI), arguing that RFI currently precludes effective SETI and that contact with advanced extraterrestrial civilizations would bring immense benefits to humanity. The negative challenges the affirmative on topicality, inherency, the desirability of the affirmative's advantage, and presents a significant disadvantage related to technology.

**Affirmative's Case:**
*   **Observation (SETI Justified):** The affirmative establishes that SETI is a respected scientific endeavor with a high probability of success due to the vastness of the universe. This point is well-supported by scientific consensus from the era.
*   **Contention I (RFI Precludes SETI):** The affirmative argues that man-made RFI is a growing problem that threatens to make SETI impossible, citing sources that indicate the atmosphere is becoming clogged and that a dedicated, protected frequency band is necessary. This establishes the inherency of the problem.
*   **Contention II (SETI is Advantageous):** The affirmative posits that contact with an advanced civilization would be highly beneficial, potentially providing solutions to humanity's problems, and dismisses fears of malevolent extraterrestrials as science fiction. This is the core advantage.

**Negative's Case:**
*   **Topicality:** The negative argues that the affirmative's plan (listening for signals) does not 'traverse space' or involve physical exploration, and that it is merely an 'effect' rather than a direct action in space. They also argue for strict grammatical and field-specific interpretations of the topic. While a common debate strategy, the affirmative successfully defends a reasonable interpretation of 'exploration' that includes listening for signals from space.
*   **Inherency Rebuttals:** The negative directly challenges the affirmative's claim that RFI is a unique barrier. They present evidence suggesting that scientists can cope with interference, that low-power uses are compatible, and that the 'waterhole' is already relatively clear. They also introduce the idea that Earth's ionosphere is a natural blocker, suggesting the problem is not solely RFI.
*   **Advantage Rebuttals:** The negative launches a multi-pronged attack on the desirability and feasibility of the affirmative's advantage. They argue that scientists have an incentive to lie or mystify information, that any message received would be extremely old and the civilization might be extinct, and crucially, that contact with an advanced civilization could be disastrous (e.g., 'technological exploitation,' 'extraterrestrial Nazis'). They also cite a source suggesting humanity is too 'dumb' for advanced civilizations to bother with.
*   **Technology Disadvantage (DA):** This is the negative's primary offensive argument. They contend that the very act of developing the technology required for SETI (e.g., decoding techniques, Project Cyclops equipment) will lead to harmful technological advancements. They argue that technology is inherently problematic, leading to environmental degradation, war, and the destruction of human autonomy. They emphasize that this harm is immediate (as tech is developed) and certain, while the affirmative's benefits are speculative and long-term.

**Clash and Evaluation:**
*   **Topicality:** The affirmative successfully defends its interpretation of the topic. The negative's arguments for a strict physical exploration requirement are countered by the affirmative's broader definition of 'exploration' and the 'and/or' in the resolution. Topicality is not a winning issue for the negative.
*   **Inherency:** The negative significantly weakens the affirmative's inherency. The affirmative largely fails to re-establish that RFI is a unique and insurmountable barrier that only their plan can solve. The negative's evidence suggesting RFI can be coped with or that the waterhole is clear undermines the necessity of the plan.
*   **Advantage:** The negative's attacks on the advantage are very strong. The arguments about the age of messages, the potential extinction of the sender, and the possibility of malevolent or exploitative ETs are compelling. The affirmative's counter-argument that ET technology would be inherently beneficial is speculative and lacks strong support against the negative's specific warnings. The negative also effectively critiques the general trustworthiness of scientific claims.
*   **Technology DA:** This is the most impactful argument. The negative establishes a clear link between the plan (enabling SETI) and the development of new technology (decoding, advanced radio telescopy). They argue that this technology, regardless of ET contact, is inherently problematic and leads to significant harms (environmental, societal, individual autonomy). The negative effectively argues for a time frame advantage, stating that the harms of technology are immediate, while the benefits of ET contact are speculative and long-term. The affirmative's responses to the DA (no link, no uniqueness, ET tech is beneficial, time frame) are generally weaker than the negative's offense. The 'no link' is countered by the negative's specific examples of tech development *for* SETI. The 'ET tech is beneficial' is an assertion. The 'time frame' argument is effectively flipped by the negative, who argues their harms are immediate.

**Overall:** The negative successfully undermines the affirmative's inherency and the desirability of its advantage. More critically, the negative presents a strong and well-linked Technology Disadvantage with significant impacts that are argued to be immediate and certain, outweighing the affirmative's speculative and long-term benefits.
ANALYSIS,
            'reason' => <<<REASON
The negative wins this debate primarily due to the strength and impact of their Technology Disadvantage, coupled with successful attacks on the affirmative's inherency and the desirability of their advantage.

1.  **Technology Disadvantage Outweighs:** The negative successfully established a clear link between the affirmative's plan (enabling SETI) and the development of new, potentially harmful technology (e.g., decoding techniques, Project Cyclops equipment). They argued that this technology, regardless of whether extraterrestrial intelligence is found or its messages decoded, inherently leads to negative consequences such as environmental degradation, war, and the destruction of human autonomy. The negative effectively argued that these harms are immediate and certain, as they occur during the *process* of developing the technology. This time frame advantage is crucial, as the affirmative's benefits are highly speculative and long-term (requiring actual contact, decoding, and benevolent information).

2.  **Weakened Affirmative Advantage:** The negative significantly undermined the affirmative's core advantage. Arguments that any received message would be extremely old, that the sending civilization might be extinct, or that contact could be disastrous (e.g., 'technological exploitation,' 'extraterrestrial Nazis') cast serious doubt on the desirability and utility of the affirmative's proposed benefit. The affirmative's counter-argument that 'ET tech is beneficial' was largely an assertion and failed to adequately address the specific harms raised by the negative.

3.  **Inherency Challenges:** The negative also successfully challenged the affirmative's inherency. Their evidence suggested that radio frequency interference can be coped with, that the 'waterhole' is already relatively clear, and that Earth's ionosphere poses a more fundamental blocking issue. The affirmative did not sufficiently re-establish the unique necessity of their plan to solve the RFI problem, weakening the problem-solution link.

While the affirmative successfully defended its topicality, the substantive debate on the case and disadvantages favored the negative. The immediate and certain harms presented by the Technology DA, combined with the speculative and potentially undesirable nature of the affirmative's advantage, led to the negative's victory.
REASON,
            'feedback_for_affirmative' => <<<FEEDBACK_FOR_AFFIRMATIVE
Your initial case setup was clear, establishing the scientific basis for SETI and identifying RFI as a significant barrier. However, several areas need improvement:

1.  **Defend Inherency More Robustly:** The negative presented several strong arguments challenging your claim that RFI uniquely precludes SETI (e.g., 'can cope with interference now,' 'waterhole is clear,' 'amateurs don't block'). You largely conceded these points or offered general rebuttals. In future debates, you must directly engage with and refute these specific claims to maintain the necessity of your plan.
2.  **Strengthen Advantage Defense:** Your advantage, while appealing, was heavily reliant on the assumption of benevolent and beneficial extraterrestrial contact. The negative effectively attacked this by arguing messages would be old, senders might be extinct, or contact could be harmful. Your response that 'ET tech is beneficial' was an assertion. You need to provide more robust evidence or logical reasoning to counter these specific harms and make your advantage more resilient.
3.  **Engage with Disadvantages More Effectively:** Your responses to the Technology DA, particularly the 'no link' and 'ET tech is beneficial' arguments, were not fully persuasive. The negative provided specific examples of technology developed *for* SETI. You need to either demonstrate why this specific technology is not harmful or why your plan uniquely mitigates those harms. Consider pre-empting common critiques of technology if your case relies on technological advancement.
FEEDBACK_FOR_AFFIRMATIVE,
            'feedback_for_negative' => <<<FEEDBACK_FOR_NEGATIVE
Your performance in this debate was strong, particularly in developing and defending the Technology Disadvantage.

1.  **Effective Use of Disadvantages:** Your Technology DA was well-structured, with clear links, impacts, and a strong time frame argument. Focusing on the immediate development of technology *for* SETI, regardless of actual contact, was a smart strategic choice that made the link very difficult for the affirmative to break. Your emphasis on the certainty of the DA versus the speculation of the advantage was also very effective.
2.  **Strong Advantage Rebuttals:** Your attacks on the affirmative's advantage were comprehensive and impactful. Arguments about the age of messages, potential extinction, and the possibility of harmful contact significantly undermined the affirmative's core benefit. Your use of sources to critique the general nature of scientific claims and elitism also added depth.
3.  **Topicality as a Tool:** While you didn't win on topicality, your arguments were well-articulated and forced the affirmative to spend significant time defending their interpretation, which is a valuable strategic outcome. However, be mindful of over-investing in T if the affirmative's interpretation is clearly reasonable.

Overall, your ability to identify and exploit the speculative nature of the affirmative's advantage and to present a concrete, immediate disadvantage was key to your success.
FEEDBACK_FOR_NEGATIVE,
        ]);
    }
}
