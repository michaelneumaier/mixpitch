<footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap justify-between">
                <!-- Quick Links -->
                <div class="w-full md:w-1/3 mb-6">
                    <h3 class="font-bold text-xl mb-4">Quick Links</h3>
                    <ul>
                        <li><a href="{{ route('about') }}" class="hover:underline">About Us</a></li>
                        <li><a href="{{ route('projects.index') }}" class="hover:underline">Projects</a></li>
                        <li><a href="{{ route('pricing') }}" class="hover:underline">Pricing & Plans</a></li>
                        <li><a href="" class="hover:underline">Support</a></li>
                        <li><a href="" class="hover:underline">Legal</a></li>
                    </ul>
                </div>
                <!-- Social Media -->
                <div class="w-full md:w-1/3 mb-6">
                    <h3 class="font-bold text-xl mb-4">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-gray-400"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="hover:text-gray-400"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="hover:text-gray-400"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="hover:text-gray-400"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <!-- Newsletter -->
                <div class="w-full md:w-1/3">
                    <h3 class="font-bold text-xl mb-4">Stay Updated</h3>
                    <form action="#" method="POST" class="flex">
                        <input type="email" name="email" placeholder="Your email"
                            class="p-2 rounded-l bg-gray-700 text-white focus:outline-none">
                        <button type="submit" class="p-2 bg-accent hover:bg-accent-focus rounded-r">Subscribe</button>
                    </form>
                </div>
            </div>
            <div class="mt-8 text-center">
                &copy; {{ date('Y') }} MixPitch. All rights reserved.
            </div>
        </div>
    </footer>