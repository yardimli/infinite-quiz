<!DOCTYPE html>
<html>
<head>
	<title>Look Detection with OpenCV.js</title>
	<style>
      body {
          transition: background-color 0.5s;
          text-align: center;
          font-family: sans-serif;
      }
      #video {
          display: none;
      }
      .slider-container {
          margin-top: 20px;
      }
	</style>
</head>
<body>
<h1>Look at the camera</h1>
<video id="video" width="640" height="480" autoplay></video>
<canvas id="canvas" width="640" height="480"></canvas>
<div class="slider-container">
	<label for="sensitivity">Sensitivity:</label>
	<input type="range" id="sensitivity" min="0" max="10" value="5">
	<span id="sensitivityValue">5</span>
</div>

<script async src="/js/opencv.js" onload="onOpenCvReady();"></script>
<script>
	let video = document.getElementById('video');
	let canvas = document.getElementById('canvas');
	let context = canvas.getContext('2d');
	let faceCascade;
	let eyeCascade;
	let sensitivitySlider = document.getElementById('sensitivity');
	let sensitivityValue = document.getElementById('sensitivityValue');

	function onOpenCvReady() {
		cv['onRuntimeInitialized'] = () => {
			// Load pre-trained classifiers for face and eye detection.
			faceCascade = new cv.CascadeClassifier();
			eyeCascade = new cv.CascadeClassifier();
			faceCascade.load('haarcascade_frontalface_default.xml');
			eyeCascade.load('haarcascade_eye.xml');

			// Access the user's webcam.
			navigator.mediaDevices.getUserMedia({ video: true, audio: false })
				.then(function(stream) {
					video.srcObject = stream;
					video.play();
					requestAnimationFrame(processVideo);
				})
				.catch(function(err) {
					console.log("An error occurred: " + err);
				});
		};
	}

	function processVideo() {
		context.drawImage(video, 0, 0, canvas.width, canvas.height);
		let src = cv.imread(canvas);
		let gray = new cv.Mat();
		cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
		let faces = new cv.RectVector();
		let eyes = new cv.RectVector();
		let msize = new cv.Size(0, 0);

		// Detect faces.
		faceCascade.detectMultiScale(gray, faces, 1.1, 3, 0, msize, msize);

		let looking = false;
		if (faces.size() > 0) {
			let face = faces.get(0);
			let faceRect = new cv.Rect(face.x, face.y, face.width, face.height);
			let faceROI = gray.roi(faceRect);

			// In each face, detect eyes.
			let sensitivity = parseInt(sensitivitySlider.value);
			eyeCascade.detectMultiScale(faceROI, eyes, 1.1, sensitivity, 0, msize, msize);

			if (eyes.size() >= 2) {
				looking = true;
			}

			// Draw rectangles around the face and eyes for visualization
			let point1 = new cv.Point(face.x, face.y);
			let point2 = new cv.Point(face.x + face.width, face.y + face.height);
			cv.rectangle(src, point1, point2, [255, 0, 0, 255]);

			for (let i = 0; i < eyes.size(); i++) {
				let eye = eyes.get(i);
				let eyePoint1 = new cv.Point(face.x + eye.x, face.y + eye.y);
				let eyePoint2 = new cv.Point(face.x + eye.x + eye.width, face.y + eye.y + eye.height);
				cv.rectangle(src, eyePoint1, eyePoint2, [0, 255, 0, 255]);
			}

			faceROI.delete();
		}

		// Change background color based on whether the user is looking.
		document.body.style.backgroundColor = looking ? 'green' : 'red';

		cv.imshow('canvas', src);
		src.delete();
		gray.delete();
		faces.delete();
		eyes.delete();

		requestAnimationFrame(processVideo);
	}

	sensitivitySlider.oninput = function() {
		sensitivityValue.innerHTML = this.value;
	}
</script>
</body>
</html>
