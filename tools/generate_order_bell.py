"""Generate CiksCoffee's busy order bell using only the Python standard library."""

from array import array
import math
from pathlib import Path
import random
import sys
import wave


def generate(destination: Path) -> None:
    sample_rate = 24_000
    duration = 1.44
    rng = random.Random(20261025)
    clusters = (0.0, 0.47, 0.94)
    strikes = [(start + hit * 0.043, hit) for start in clusters for hit in range(8)]
    partials = ((1.0, 0.65), (1.49, 0.31), (2.14, 0.18), (2.92, 0.10), (4.10, 0.05))
    samples = []
    phase = 0.0
    for index in range(round(sample_rate * duration)):
        at = index / sample_rate
        metallic = 0.0
        for start, hit in strikes:
            age = at - start
            if age < 0 or age > 0.45:
                continue
            fundamental = 930 if hit % 2 == 0 else 1290
            attack = 1 - math.exp(-age / 0.0015)
            for ratio, strength in partials:
                metallic += strength * attack * math.exp(-age * (14 + ratio * 2)) * math.sin(2 * math.pi * fundamental * ratio * age)
        active = max((math.sin(math.pi * (at - start) / 0.39) ** 0.6 if 0 < at - start < 0.39 else 0) for start in clusters)
        phase += 2 * math.pi * (1080 + 240 * math.sin(2 * math.pi * 15 * at)) / sample_rate
        signal = 0.70 * metallic + active * (0.14 * math.sin(phase) + 0.035 * rng.uniform(-1, 1))
        fade = min(1.0, at / 0.004, max(0.0, (duration - at) / 0.10))
        samples.append(math.tanh(signal * 2.8) * fade)

    peak = max(abs(value) for value in samples)
    samples = [value * 0.96 / peak for value in samples]
    pcm = array('h', (round(value * 32767) for value in samples))
    if sys.byteorder != 'little':
        pcm.byteswap()
    destination.parent.mkdir(parents=True, exist_ok=True)
    with wave.open(str(destination), 'wb') as output:
        output.setnchannels(1)
        output.setsampwidth(2)
        output.setframerate(sample_rate)
        output.writeframes(pcm.tobytes())
    rms = math.sqrt(sum(value * value for value in samples) / len(samples))
    print(f'{destination.name}: {duration:.2f}s, peak={max(abs(v) for v in samples):.3f}, RMS={rms:.3f}, {destination.stat().st_size} bytes')


if __name__ == '__main__':
    generate(Path(__file__).resolve().parents[1] / 'public' / 'audio' / 'bel-pesanan-ramai.wav')
