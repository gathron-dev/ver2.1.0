<?php
// includes/action_command.php
header('Content-Type: application/javascript; charset=utf-8');
?>
// ==== action_command モジュール ====
// 絵文字をバブル横に、左右距離を自分／相手で調整できるように改良

(() => {

  // ==== 共通ユーティリティ: 絵文字配置関数（自分だけ狭く設定） ====
  /**
   * bubble の横に emoji 要素を表示する
   * @param {HTMLElement} bubble  対象のチャットバブル要素
   * @param {HTMLElement} emoji   表示する絵文字要素
   * @param {boolean}     isMe    自分のバブルかどうか
   * @param {number}      size    絵文字の幅・高さ（px）
   */
  function attachEmoji(bubble, emoji, isMe, size = 60) {
    // 自分のバブルは margin 20、他人は margin 30
    const margin = isMe ? 30 : 10;

    // 親行を relative にして絶対配置可能に
    const row = bubble.closest('.chat-row');
    row.style.position = row.style.position || 'relative';

    // bubble と row の位置を取得
    const bubRect = bubble.getBoundingClientRect();
    const rowRect = row.getBoundingClientRect();

    // 横位置：自分は左、相手は右
    const left = isMe
      ? bubRect.left - rowRect.left - size - margin
      : bubRect.right - rowRect.left + margin;

    // 縦位置：バブル中央に合わせる
    const top = bubRect.top - rowRect.top + (bubRect.height - size) / 2;

    Object.assign(emoji.style, {
      left: `${left}px`,
      top:  `${top}px`
    });
    row.appendChild(emoji);
  }


  // ==== コマンド定義 & 実行関数 ====
  const commands = [

    // --- 笑いアクション -------------------------------------------------------------------
    {
      name: 'laugh_shake',
      regex: /(?:😁|😀|😄|藁|笑|嬉|喜|smile|laugh|psyched|delighte|giggle|chuckle)/i,
      handler: (bubble, isMe) => {
        // 1) 小刻みな上下揺れ
        const subtleShake = [
          { transform: 'translateY(0)' },
          { transform: 'translateY(-8px)' },
          { transform: 'translateY(8px)' },
          { transform: 'translateY(-4px)' },
          { transform: 'translateY(4px)' },
          { transform: 'translateY(0)' }
        ];
        const shakeOpts = { duration: 400, iterations: 2, easing: 'ease-in-out' };
        bubble.animate(subtleShake, shakeOpts);

        // 2) 😁絵文字を作成し配置＆揺れ＋点滅フェード
        const emoji = document.createElement('div');
        emoji.textContent = '😁';
        Object.assign(emoji.style, {
          position:      'absolute',
          width:         '60px',
          height:        '60px',
          lineHeight:    '60px',
          fontSize:      '60px',
          pointerEvents: 'none'
        });

        attachEmoji(bubble, emoji, isMe, 60);
        emoji.animate(subtleShake, shakeOpts);

        // 3) 点滅フェード (1→0.3→1)、5秒間隔で繰り返し
        const blinkFrames = [
          { opacity: 1, offset: 0  },  // 開始時点は100%
          { opacity: 1, offset: 0  },  // 0.3 0.05で30%
          { opacity: 1, offset: 0  },  // 1 0.1で100%
          { opacity: 1, offset: 0  },  // 0.3 0.15で30%
          { opacity: 1, offset: 0  },  // 1 0.2で100%
          { opacity: 1, offset: 1  }   // 最後は100%のまま
        ];
        // iterations:Infinityで繰り返し 10000で10秒
        const blinkOpts = { duration: 10000, iterations: Infinity, easing: 'ease-in-out' };
        emoji.animate(blinkFrames, blinkOpts);
      }
    },

    // --- 悲しみアクション -------------------------------------------------------------------
    {
      name: 'sad_shake',
      regex: /(?:🥲|😭|🥲|哀|悲|泣|sad|bummer|tear|bummed)/i,
      handler: (bubble, isMe) => {
        // 1) 小刻みな上下揺れ
        const subtleShake = [
          { transform: 'translateY(0)' },
          { transform: 'translateY(-8px)' },
          { transform: 'translateY(8px)' },
          { transform: 'translateY(-4px)' },
          { transform: 'translateY(4px)' },
          { transform: 'translateY(0)' }
        ];
        const shakeOpts = { duration: 400, iterations: 2, easing: 'ease-in-out' };
        bubble.animate(subtleShake, shakeOpts);

        // 2) 😭絵文字を作成し配置＆揺れ＋点滅フェード
        const emoji = document.createElement('div');
        emoji.textContent = '😭';
        Object.assign(emoji.style, {
          position:      'absolute',
          width:         '60px',
          height:        '60px',
          lineHeight:    '60px',
          fontSize:      '60px',
          pointerEvents: 'none'
        });

        attachEmoji(bubble, emoji, isMe, 60);
        emoji.animate(subtleShake, shakeOpts);

        // 3) 点滅フェード (1→0.3→1)、5秒間隔で繰り返し
        const blinkFrames = [
          { opacity: 1, offset: 0  },  // 開始時点は100%
          { opacity: 1, offset: 0  },  // 0.3 0.05で30%
          { opacity: 1, offset: 0  },  // 1 0.1で100%
          { opacity: 1, offset: 0  },  // 0.3 0.15で30%
          { opacity: 1, offset: 0  },  // 1 0.2で100%
          { opacity: 1, offset: 1  }   // 最後は100%のまま
        ];
        // iterations:Infinityで繰り返し 10000で10秒
        const blinkOpts = { duration: 10000, iterations: Infinity, easing: 'ease-in-out' };
        emoji.animate(blinkFrames, blinkOpts);
      }
    },

// --- 拳銃アクション -------------------------------------------------------------------
{
  name: 'gun_shake',
  regex: /(?:🔫|😡|😤|殺|怒|撃|射|shot|shoot|gun|kill|angry)/i,
  handler: (bubble, isMe) => {
    // 1) バブルを上下に小刻み揺れさせる
    const subtleShake = [
      { transform: 'translateX(0)' },
      { transform: 'translateX(-8px)' },
      { transform: 'translateX(8px)' },
      { transform: 'translateX(-4px)' },
      { transform: 'translateX(4px)' },
      { transform: 'translateX(0)' }
    ];
    const shakeOpts = { duration: 400, iterations: 2, easing: 'ease-in-out' };
    bubble.animate(subtleShake, shakeOpts);

    // 2) 🔫絵文字を作成してバブル横に20px＋自分だけ追加10pxで配置
    const emoji = document.createElement('div');
    emoji.textContent = '🔫';
    Object.assign(emoji.style, {
      position:      'absolute',
      width:         '60px',
      height:        '60px',
      lineHeight:    '60px',
      fontSize:      '60px',
      pointerEvents: 'none'
    });
    const size        = 60;
    const marginEmoji = 30;
    const extraEmoji  = isMe ? 0 : 0; // 自分だけさらに10px
    const row         = bubble.closest('.chat-row');
    row.style.position = row.style.position || 'relative';
    const bubRect     = bubble.getBoundingClientRect();
    const rowRect     = row.getBoundingClientRect();
    const leftEmoji   = isMe
      ? bubRect.left  - rowRect.left - size - marginEmoji - extraEmoji
      : bubRect.right - rowRect.left + marginEmoji + extraEmoji;
    const topEmoji    = (rowRect.height - size) / 2;
    emoji.style.left = `${leftEmoji}px`;
    emoji.style.top  = `${topEmoji}px`;
    if (!isMe) emoji.style.transform = 'scaleX(-1)'; // 参加者は反転
    row.appendChild(emoji);
    // 絵文字も上下揺れ（反転維持）
    const emojiShake = subtleShake.map(f => ({
      transform: isMe ? f.transform : `scaleX(-1) ${f.transform}`
    }));
    emoji.animate(emojiShake, shakeOpts);

    // 3) 銃口から3連パルス発射
    const emoRect = emoji.getBoundingClientRect();
    const muzzleX = isMe
      ? emoRect.left  - rowRect.left
      : emoRect.right - rowRect.left;
    const muzzleY = emoRect.top - rowRect.top + emoRect.height / 2;
    for (let i = 0; i < 3; i++) {
      setTimeout(() => {
        const pulse = document.createElement('div');
        Object.assign(pulse.style, {
          position:      'absolute',
          left:          `${muzzleX - 10}px`,
          top:           `${muzzleY - 10}px`,
          width:         '20px',
          height:        '20px',
          border:        '2px solid red',
          borderRadius:  '50%',
          opacity:       1,
          pointerEvents: 'none',
          transform:     'scale(0)'
        });
        row.appendChild(pulse);
        pulse.animate(
          [
            { transform: 'scale(0)', opacity: 1 },
            { transform: 'scale(2)', opacity: 0 }
          ],
          { duration: 500, easing: 'ease-out', fill: 'forwards' }
        ).onfinish = () => row.removeChild(pulse);
      }, i * 100);
    }

    // 4) レーザービーム発射（バブルから20px＋自分だけさらに30pxオフセット）
    const marginBeam = 0;
    const extraBeam  = isMe ? 100 : 0;  // 自分だけ30px左にずらす
    const beamStartX = isMe
      ? muzzleX - marginBeam - extraBeam
      : muzzleX + marginBeam + extraBeam;
    const beam = document.createElement('div');
    Object.assign(beam.style, {
      position:        'absolute',
      left:            `${beamStartX}px`,
      top:             `${muzzleY - 2}px`,
      width:           '100px',
      height:          '4px',
      background:      'red',
      borderRadius:    '2px',
      pointerEvents:   'none',
      transformOrigin: isMe ? '100% 50%' : '0 50%',
      transform:       'scaleX(0)'
    });
    row.appendChild(beam);
    beam.animate(
      [
        { transform: 'scaleX(0)', opacity: 1 },
        { transform: 'scaleX(1)', opacity: 0 }
      ],
      { duration: 500, easing: 'ease-out', fill: 'forwards' }
    ).onfinish = () => row.removeChild(beam);

    // 5) 🔫絵文字の点滅フェード（2回素早く→5秒待機を無限ループ）
        const blinkFrames = [
          { opacity: 1, offset: 0  },  // 開始時点は100%
          { opacity: 1, offset: 0  },  // 0.3 0.05で30%
          { opacity: 1, offset: 0  },  // 1 0.1で100%
          { opacity: 1, offset: 0  },  // 0.3 0.15で30%
          { opacity: 1, offset: 0  },  // 1 0.2で100%
          { opacity: 1, offset: 1  }   // 最後は100%のまま
        ];
        // iterations:Infinityで繰り返し 10000で10秒
        const blinkOpts = { duration: 10000, iterations: Infinity, easing: 'ease-in-out' };
        emoji.animate(blinkFrames, blinkOpts);
  }
}

    // ==== ここに次のコマンドを追加できます ====
    
  ];

  // グローバルにコマンド実行関数を公開
  window.runCommands = bubble => {
    const isMe = bubble.closest('.chat-row').classList.contains('me');
    const text = bubble.dataset.original || bubble.textContent;
    commands.forEach(cmd => {
      if (cmd.regex.test(text)) {
        try { cmd.handler(bubble, isMe); }
        catch (e) { console.error(`command ${cmd.name} failed`, e); }
      }
    });
  };

  // ==== 初期化 & MutationObserver 設定 ====
  document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('messages');
    if (!container) return;
    container.querySelectorAll('.chat-bubble').forEach(window.runCommands);
    new MutationObserver(muts => {
      muts.forEach(m => {
        m.addedNodes.forEach(node => {
          if (node.nodeType !== 1) return;
          node.querySelectorAll('.chat-bubble').forEach(window.runCommands);
        });
      });
    }).observe(container, { childList: true, subtree: true });
  });

})();