# vMix Playback Behavior - Real Test Notes

Date: 2026-06-21

Context:

- vMix version tested: `29.0.0.48 Trial`
- endpoint tested: `http://localhost:8088/api`
- test media created on Windows host: `C:\wamp64\www\inovixora\balafon-tv-automation\storage\app\media-demo\automation-test.wav`
- media duration: `3000 ms`

## 1. Real test method

The following real sequence was executed against the local vMix instance:

1. create a short real media file on the Windows host
2. inject it with `AddInput` using:
   - `Function=AddInput`
   - `Value=AudioFile|C:\...\automation-test.wav`
3. poll `GET /api`
4. trigger playback using:
   - `Function=Play&Input={number}`
5. poll XML during playback
6. observe `state`, `position` and `duration`

## 2. Observed XML fields

For the injected media input, the XML exposed:

- `title="automation-test.wav"`
- `state`
- `position`
- `duration`
- `type="AudioFile"`
- `number`
- `key`

These fields are sufficient to monitor progression of a playing media.

## 3. Real observed behavior

### Before playback

- `state = Paused`
- `position = 0`
- `duration = 3000`

### During playback

Observed values during real polling:

- `Running / position 730 / duration 3000`
- `Running / position 1511 / duration 3000`
- `Running / position 2261 / duration 3000`
- `Running / position 3000 / duration 3000`
- then `Completed / position 3000 / duration 3000`

### Fine polling test at 100 ms

With `Restart` followed by `Play`, the input evolved like this:

- `tick 19`: `Running / position 2912 / duration 3000`
- `tick 20`: `Completed / position 3000 / duration 3000`

Important note:

- `Restart` alone resets the media to `Paused` at `position = 0`
- `Restart` does not resume playback by itself
- a second explicit `Play` is required after `Restart`

## 4. Answers to the required questions

### Question 1

Can we detect the real end of a media via XML API?

Answer: **Yes**

Reliable observable signal:

- `state` changes from `Running` to `Completed`

Supporting signals:

- `position` reaches `duration`

### Question 2

Can we retrieve remaining duration?

Answer: **Yes**

Calculation:

- `remaining_ms = duration - position`

This value is directly derivable from the XML.

### Question 3

Can we know precisely when a media is finished?

Answer: **Yes, with operational precision, but not as an event callback**

What vMix gives:

- a pollable `state`
- a pollable `position`
- a pollable `duration`

What vMix does not give:

- no webhook
- no push event
- no exact callback at media completion

Therefore:

- completion is known when the next XML poll shows `Completed`
- the precision depends on polling interval

### Question 4

What is the most reliable strategy?

Options:

- polling XML
- duration calculation
- combination of both

Answer: **combination of both**

Recommended strategy:

1. use XML polling as the source of truth
2. use `duration - position` as anticipation data
3. declare completion when one of these conditions is met:
   - `state = Completed`
   - or `position >= duration` and the input is no longer `Running`
4. keep a short safety buffer before switching to failed / stalled logic

## 5. Recommended monitoring strategy for Balafon

### Primary rule

Use **XML polling** as the authoritative runtime signal.

### Secondary rule

Use **remaining duration** to prepare the next item slightly before completion.

### Recommended decision logic

For the currently playing item:

1. poll every `500 ms` to `1000 ms`
2. read:
   - `state`
   - `position`
   - `duration`
3. compute:
   - `remaining_ms = duration - position`
4. transitions:
   - if `state = Running`: item is on air
   - if `remaining_ms <= preload_threshold_ms`: prepare next item
   - if `state = Completed`: mark item completed and advance
   - if `state` is unexpected or input disappears: mark failed and escalate

### Practical thresholds

Suggested first implementation:

- polling interval: `1000 ms`
- preload threshold: `1500 ms` to `2500 ms`
- timeout guard: `duration + safety_margin`

Suggested safety margin:

- `2000 ms` minimum

## 6. Practical limitations

- vMix does not push completion events
- precision is bounded by polling frequency
- if polling is too slow, next-item switching will be slightly delayed
- if polling is too aggressive, load increases unnecessarily

## 7. Architecture conclusion

For Balafon automation, the most reliable playback strategy is:

1. launch media through vMix API
2. monitor the active automation item by XML polling
3. compute remaining time continuously
4. prepare the next item shortly before completion
5. switch definitively when XML confirms completion

## 8. Final recommendation

The automation engine should not rely on duration calculation alone.

It should use:

- `XML polling` for actual runtime truth
- `duration calculation` for anticipation
- `state = Completed` as the canonical completion signal

This is the strategy to implement in `Domains/Automation`.
