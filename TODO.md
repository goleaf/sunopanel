# TODO List

## Requirements
- Parse song information from textarea with format: title.mp3|mp3_url|image_url|genres
- Download MP3 files and images
- Create MP4 files using MP3 audio and static image
- Process genres (create if not exists)
- Run processing in background with progress bar
- Simple UI with 3 menu items: Add / Songs / Genres

## Implementation Tasks
- [x] Create database migrations for tracks and genres
- [x] Create models for Track and Genre
- [x] Create controllers for form input, tracks listing, and genres
- [x] Create background job for file processing
- [x] Implement progress tracking for background jobs
- [x] Create views for Add, Songs, and Genres pages
- [x] Install and configure required dependencies for MP4 creation
- [x] Implement UI with TailwindCSS and DaisyUI

## Technical Requirements
- MP3 and image download from external URLs
- Convert MP3 + image to MP4
- Background job processing
- Progress bar for long-running tasks

i need to parse from textare this info
Fleeting Love (儚い愛).mp3|https://cdn1.suno.ai/69c0d3c4-a06f-471e-a396-4cb09c9ec2b6.mp3|https://cdn2.suno.ai/image_a07fbe33-8ee5-4f91-bb8d-180cbb49e5fe.jpeg|City pop,80s
Palakpakan.mp3|https://cdn1.suno.ai/9a00dc20-9640-4150-9804-d8a179ce860c.mp3|https://cdn2.suno.ai/image_9a00dc20-9640-4150-9804-d8a179ce860c.jpeg|city pop
ジャカジャカ.mp3|https://cdn1.suno.ai/837cd038-c104-405b-b1d5-bafa924a277f.mp3|https://cdn2.suno.ai/image_837cd038-c104-405b-b1d5-bafa924a277f.jpeg|city pop
nihongo jouzu.mp3|https://cdn1.suno.ai/79ef4474-cea8-433a-9bdc-5fe3482fd410.mp3|https://cdn2.suno.ai/79ef4474-cea8-433a-9bdc-5fe3482fd410_e90ebc18.jpeg|city pop
无言的告别.mp3|https://cdn1.suno.ai/86c03eaa-facb-487c-96d5-015a0d3fcc72.mp3|https://cdn2.suno.ai/image_463417b7-1282-4083-a681-c11848872ba1.jpeg|lofi,City pop,R&B
City of Sound.mp3|https://cdn1.suno.ai/52f2608e-d8fe-44e7-ab9a-5d6778dea12e.mp3|https://cdn2.suno.ai/52f2608e-d8fe-44e7-ab9a-5d6778dea12e_8f0f8ca2.jpeg|City pop synthwave vaporwave
Neon Nights.mp3|https://cdn1.suno.ai/bfd2531b-f8a5-432c-9bf8-8d5c2cac3a26.mp3|https://cdn2.suno.ai/bfd2531b-f8a5-432c-9bf8-8d5c2cac3a26_4ba38742.jpeg|city pop synthwave vaporwave
Humor sentido 3.mp3|https://cdn1.suno.ai/f474c102-46df-47a9-9226-4f6bb54a3f50.mp3|https://cdn2.suno.ai/image_f474c102-46df-47a9-9226-4f6bb54a3f50.jpeg|City pop psybient
City Lights.mp3|https://cdn1.suno.ai/e0ec684c-5a04-42f2-a656-6ee8fa05c880.mp3|https://cdn2.suno.ai/e0ec684c-5a04-42f2-a656-6ee8fa05c880_ffc2643f.jpeg|City pop
Loop the loop.mp3|https://cdn1.suno.ai/25ad5bcc-266b-4777-8d5f-1fa48a3b99af.mp3|https://cdn2.suno.ai/image_8299a9b0-77e6-42c7-8cbe-a41494ed30df.jpeg|CITY POP
Midnight Mirage.mp3|https://cdn1.suno.ai/4aaf14ca-ea58-4e97-87e3-b19aca31595a.mp3|https://cdn2.suno.ai/image_4aaf14ca-ea58-4e97-87e3-b19aca31595a.jpeg|acid rock city pop
キラメキ・ステップ.mp3|https://cdn1.suno.ai/a488d07e-e8c3-4335-aa18-f384cb133275.mp3|https://cdn2.suno.ai/a488d07e-e8c3-4335-aa18-f384cb133275_ba783930.jpeg|80s City POP
Summer in Tokyo (ALT).mp3|https://cdn1.suno.ai/7a41ee58-c865-4ad0-83e3-817d36188eea.mp3|https://cdn2.suno.ai/image_7d3092ac-8c3a-4f5d-bddf-c9e5ac8dcd5f.jpeg|New age funk,city pop,brass band
Gigolos for Laundry.mp3|https://cdn1.suno.ai/328e1f08-32ab-4929-8c1c-38f94b71f1e1.mp3|https://cdn2.suno.ai/328e1f08-32ab-4929-8c1c-38f94b71f1e1_56694827.jpeg|disco,japanese Nostalgic city-pop
I've Never Loved Like This.mp3|https://cdn1.suno.ai/07a1add8-fbf9-48e9-b1ad-6b6a4822493e.mp3|https://cdn2.suno.ai/07a1add8-fbf9-48e9-b1ad-6b6a4822493e_478a2e50.jpeg|AOR,city-pop
Radio Waves.mp3|https://cdn1.suno.ai/8d0149d6-236d-4d2b-9d2e-0923a4311895.mp3|https://cdn2.suno.ai/8d0149d6-236d-4d2b-9d2e-0923a4311895_cbda2f8e.jpeg|City POP
CÂY ƠI!.mp3|https://cdn1.suno.ai/71f013a4-0903-4991-a990-f1051a59a193.mp3|https://cdn2.suno.ai/image_71f013a4-0903-4991-a990-f1051a59a193.jpeg|city pop
ElectricKinetic  (RemixRearrange).mp3|https://cdn1.suno.ai/a5fb3403-74fd-4819-9417-409af40e603c.mp3|https://cdn2.suno.ai/a5fb3403-74fd-4819-9417-409af40e603c_2c38cfb6.jpeg|city pop,dance,pop,clean production
My heart is break again.mp3|https://cdn1.suno.ai/158326b3-e0bc-4939-bd30-26a49ef86d55.mp3|https://cdn2.suno.ai/image_158326b3-e0bc-4939-bd30-26a49ef86d55.jpeg|lo-fi jazz city pop

download files, from mp3 make mp4 file with audio from mp3 and video linne as image, create mp4. after last separator is a gennres by comma, need to create attach to genre, if not exists then create new by slug. make it automaticaly in background with progresbar. make only 3 menu - add / songs / genres