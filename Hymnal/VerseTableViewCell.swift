//
//  VerseTableViewCell.swift
//  Hymnal
//
//  Created by Jeremy Olson on 8/15/16.
//  Copyright © 2016 Jeremy Olson. All rights reserved.
//

import UIKit

class VerseTableViewCell: UITableViewCell {

    @IBOutlet weak var verseTextView: UITextView!
    @IBOutlet weak var verseNumberLabel: UILabel!
    
    override func awakeFromNib() {
        super.awakeFromNib()
        // Initialization code
    }

    override func setSelected(_ selected: Bool, animated: Bool) {
        super.setSelected(selected, animated: animated)

        
        // Configure the view for the selected state
    }

}
